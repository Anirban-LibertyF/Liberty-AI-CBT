<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

define('APP_ROOT', dirname(__DIR__));

require_once __DIR__ . '/MathRenderer.php';
require_once __DIR__ . '/Svg.php';
require_once __DIR__ . '/MoleculeSvg.php';
require_once __DIR__ . '/BioSvg.php';
require_once __DIR__ . '/ChartSvg.php';
require_once __DIR__ . '/Freshness.php';
require_once __DIR__ . '/Gemini.php';
require_once __DIR__ . '/Exams.php';
require_once __DIR__ . '/Attempts.php';
require_once __DIR__ . '/Coach.php';
require_once __DIR__ . '/layout.php';

// Must happen before any HTML/JSON output: once the body starts streaming, PHP can no
// longer send the Set-Cookie header, so a session_start() called later (e.g. lazily from
// csrf_token() deep inside a page) silently fails to persist the session cookie — every
// request then gets a fresh, empty session and CSRF checks fail on every single submit.
start_session();

// ---------------------------------------------------------------- config

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        // CBT_CONFIG lets a deployment keep its config file outside the project folder.
        $file = getenv('CBT_CONFIG') ?: APP_ROOT . '/config.php';
        $cfg = is_file($file) ? require $file : require APP_ROOT . '/config.sample.php';
    }
    $value = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

// ---------------------------------------------------------------- errors

/** User-facing failure. The message is safe to show. */
final class AppError extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}

function is_api_request(): bool
{
    return defined('IS_API') && IS_API === true;
}

set_exception_handler(function (Throwable $e): void {
    $status = $e instanceof AppError ? $e->status : 500;
    if (!$e instanceof AppError) {
        error_log((string) $e);
    }
    $message = $e instanceof AppError
        ? $e->getMessage()
        : (($e instanceof PDOException && in_array($e->getCode(), [1049, 2002, 1045, '1049', '2002', '1045'], true))
            ? 'LibertyCBT.Ai is temporarily unavailable. Please try again in a few minutes.'
            : 'Something went wrong. Please try again.');
    if (!headers_sent()) {
        http_response_code($status);
    }
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        return;
    }
    if (headers_sent()) {
        // The page had already started streaming before this error — too late for the full layout.
        echo '<p style="font-family:system-ui;padding:2rem">' . h($message) . ' <a href="index.php">Back to home</a></p>';
        return;
    }
    page_head('LibertyCBT.Ai', 'min-h-screen bg-red-50 text-slate-900');
    ?>
    <header class="border-b border-red-100 bg-white backdrop-blur-md sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="logo-3d w-10 h-10 rounded-xl bg-gradient-to-br from-red-600 to-orange-500 flex items-center justify-center shadow-lg shadow-red-600/20 text-white font-extrabold tracking-tight">LC</div>
          <div>
            <h1 class="text-lg sm:text-xl font-bold tracking-tight"><span class="brand-gradient">LibertyCBT</span><span class="text-slate-900">.Ai</span></h1>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <?php site_nav(); ?>
        </div>
      </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-16 flex items-center justify-center">
      <div class="max-w-md w-full p-6 rounded-2xl bg-white border border-red-100 text-center">
        <?= icon('circle-alert', 'w-8 h-8 text-rose-500 mx-auto mb-3') ?>
        <p class="text-slate-700"><?= h($message) ?></p>
      </div>
    </main>
    <?php
    page_foot();
});

// ---------------------------------------------------------------- database

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db.host', '127.0.0.1'),
            (int) config('db.port', 3306),
            config('db.name', 'cbt_portal')
        );
        $pdo = new PDO($dsn, (string) config('db.user', 'root'), (string) config('db.pass', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

// ---------------------------------------------------------------- session / csrf

function start_session(): void
{
    // After session_write_close() $_SESSION stays readable; don't reopen (and re-lock) the session then.
    if (session_status() === PHP_SESSION_NONE && !isset($_SESSION)) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
        session_name('nexuscbt');
        session_start();
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(?string $given): void
{
    start_session();
    $expected = $_SESSION['csrf'] ?? '';
    if ($expected === '' || $given === null || !hash_equals($expected, $given)) {
        throw new AppError('Your session expired. Reload the page and try again.', 403);
    }
}

/** Remember that this browser session created/owns an exam or attempt. */
function grant(string $kind, int $id): void
{
    start_session();
    $_SESSION['own'][$kind][$id] = true;
}

function owns(string $kind, int $id): bool
{
    start_session();
    return !empty($_SESSION['own'][$kind][$id]);
}

// ---------------------------------------------------------------- request helpers

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * JSON endpoints: POST only, CSRF-checked. Returns the decoded body.
 * The token may come in the X-CSRF-Token header or as "_csrf" in the body
 * (navigator.sendBeacon cannot set headers).
 *
 * @param bool $releaseSession close the session lock early so parallel requests don't queue
 */
function api_post(bool $releaseSession = false): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new AppError('POST required.', 405);
    }
    $raw = file_get_contents('php://input') ?: '';
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        $body = [];
    }
    check_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['_csrf'] ?? null));
    if ($releaseSession) {
        session_write_close();
    }
    return $body;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** JSON for embedding inside a <script> tag. */
function js_json(mixed $data): string
{
    return json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
}

function icon(string $name, string $class = 'w-4 h-4'): string
{
    return '<i data-lucide="' . h($name) . '" class="' . h($class) . '"></i>';
}

/** Stored timestamps are UTC; shown to users in this timezone (change here if your exams run elsewhere). */
const DISPLAY_TIMEZONE = 'Asia/Kolkata';

/** Formats a UTC "Y-m-d H:i:s" (or any strtotime-parsable UTC string) in the display timezone. */
function local_time(string $utc, string $format = 'd M Y, h:i A'): string
{
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(DISPLAY_TIMEZONE));
    return $dt->format($format);
}

function format_clock(int $seconds): string
{
    $seconds = max(0, $seconds);
    $hrs = intdiv($seconds, 3600);
    $mins = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;
    return $hrs > 0
        ? sprintf('%02d:%02d:%02d', $hrs, $mins, $secs)
        : sprintf('%02d:%02d', $mins, $secs);
}

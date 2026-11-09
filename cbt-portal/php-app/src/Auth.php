<?php
declare(strict_types=1);

/** Accounts: register, login, logout. Passwords are stored with password_hash() only. */
final class Auth
{
    /** The signed-in user (id, name, email, phone) or null. Cached per request. */
    public static function user(): ?array
    {
        static $loaded = false, $user = null;
        if (!$loaded) {
            $loaded = true;
            start_session();
            $id = (int) ($_SESSION['uid'] ?? 0);
            if ($id > 0) {
                $st = db()->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
                $st->execute([$id]);
                $user = $st->fetch() ?: null;
                if (!$user) {
                    unset($_SESSION['uid']);
                }
            }
        }
        return $user;
    }

    public static function require(): array
    {
        return self::user() ?? throw new AppError('Please log in or create an account to continue.', 401);
    }

    /** Returns the list of unmet password rules (empty = acceptable). */
    public static function passwordProblems(string $pw): array
    {
        $p = [];
        if (strlen($pw) < 8) { $p[] = 'at least 8 characters'; }
        if (!preg_match('/[A-Z]/', $pw)) { $p[] = 'one capital letter'; }
        if (!preg_match('/[0-9]/', $pw)) { $p[] = 'one digit'; }
        if (!preg_match('/[^A-Za-z0-9]/', $pw)) { $p[] = 'one special character'; }
        return $p;
    }

    public static function normalizePhone(string $raw): ?string
    {
        $d = preg_replace('/[\s\-()]/', '', $raw) ?? '';
        $d = preg_replace('/^(\+?91|0)(?=\d{10}$)/', '', $d) ?? '';
        return preg_match('/^[6-9]\d{9}$/', $d) ? $d : null;
    }

    public static function register(array $in): array
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) ($in['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            throw new AppError('Please enter your full name.');
        }
        if (($in['country_code'] ?? '+91') !== '+91') {
            throw new AppError('Only Indian (+91) mobile numbers are supported.');
        }
        $phone = self::normalizePhone((string) ($in['phone'] ?? ''))
            ?? throw new AppError('Enter a valid 10-digit Indian mobile number (starting with 6, 7, 8 or 9).');
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            throw new AppError('Enter a valid email address.');
        }
        $pw = (string) ($in['password'] ?? '');
        if (isset($in['confirm']) && !hash_equals($pw, (string) $in['confirm'])) {
            throw new AppError('The two passwords do not match.');
        }
        $problems = self::passwordProblems($pw);
        if ($problems || strlen($pw) > 200) {
            throw new AppError('Password needs ' . implode(', ', $problems ?: ['fewer than 200 characters']) . '.');
        }

        $pdo = db();
        $st = $pdo->prepare('SELECT email, phone FROM users WHERE email = ? OR phone = ?');
        $st->execute([$email, $phone]);
        foreach ($st->fetchAll() as $row) {
            throw new AppError($row['email'] === $email
                ? 'An account with this email already exists. Please log in.'
                : 'An account with this mobile number already exists. Please log in.', 409);
        }
        $pdo->prepare('INSERT INTO users (name, phone, email, password_hash) VALUES (?, ?, ?, ?)')
            ->execute([$name, $phone, $email, password_hash($pw, PASSWORD_DEFAULT)]);
        $id = (int) $pdo->lastInsertId();
        self::signIn($id);
        return ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone];
    }

    public static function login(array $in): array
    {
        start_session();
        $now = time();
        if (($_SESSION['login_lock'] ?? 0) > $now) {
            throw new AppError('Too many attempts. Please wait a minute and try again.', 429);
        }
        $id = strtolower(trim((string) ($in['identifier'] ?? '')));
        $pw = (string) ($in['password'] ?? '');
        $phone = self::normalizePhone($id);
        $st = db()->prepare('SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1');
        $st->execute([$id, $phone ?? '-']);
        $row = $st->fetch();
        // Verify against a dummy hash when the user is unknown so timing doesn't reveal which accounts exist.
        $hash = $row['password_hash'] ?? '$2y$10$usesomesillystringforsalt.u3vJxZ0nQ0q0Yk8c0cY0hX0yq0b0e';
        if (!password_verify($pw, $hash) || !$row) {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) {
                $_SESSION['login_fails'] = 0;
                $_SESSION['login_lock'] = $now + 60;
            }
            throw new AppError('Incorrect email/mobile or password.', 401);
        }
        $_SESSION['login_fails'] = 0;
        self::signIn((int) $row['id']);
        return ['id' => (int) $row['id'], 'name' => $row['name'], 'email' => $row['email'], 'phone' => $row['phone']];
    }

    private static function signIn(int $id): void
    {
        start_session();
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;
    }

    public static function logout(): void
    {
        start_session();
        unset($_SESSION['uid'], $_SESSION['own']);
    }
}

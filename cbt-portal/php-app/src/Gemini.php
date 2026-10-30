<?php
declare(strict_types=1);

/** Thin Gemini REST client with exponential backoff. The API key never leaves the server. */
final class Gemini
{
    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    private static function apiKey(): string
    {
        return trim((string) (config('gemini.api_key') ?: getenv('GEMINI_API_KEY') ?: ''));
    }

    /**
     * Sends a generateContent payload and returns the model's text part.
     * Retries on network errors, 429 and 5xx; gives up immediately on other 4xx
     * (a bad key or malformed request won't fix itself).
     */
    public static function generate(array $payload, int $maxRetries = 3): string
    {
        $key = self::apiKey();
        if ($key === '') {
            throw new AppError('AI test creation is not available right now. You can still take a ready-made mock test.', 503);
        }
        if (!function_exists('curl_init')) {
            throw new AppError('AI test creation is not available right now. Please try again later.', 500);
        }

        $model = (string) config('gemini.model', 'gemini-3.5-flash-lite');
        $url = (string) (config('gemini.endpoint') ?: 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent');
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $lastError = 'unknown error';
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 0) {
                sleep(2 ** ($attempt - 1)); // 1s, 2s, 4s
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => (int) config('gemini.timeout', 90),
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                $lastError = 'network error: ' . $curlError;
                continue;
            }
            if ($status === 429 || $status >= 500) {
                $lastError = "HTTP $status";
                continue;
            }
            if ($status >= 400) {
                $detail = json_decode($response, true)['error']['message'] ?? '';
                error_log("Gemini HTTP $status: $detail");
                throw new AppError('We could not create the test right now. Please try again in a moment.', 502);
            }

            $text = json_decode($response, true)['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($text) || $text === '') {
                $lastError = 'empty response';
                continue;
            }
            return $text;
        }

        error_log("Gemini failed after retries: $lastError");
        throw new AppError('Creating the test took too long. Please try again.', 502);
    }
}

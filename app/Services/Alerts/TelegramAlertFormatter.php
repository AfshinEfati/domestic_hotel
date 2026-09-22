<?php

namespace App\Services\Alerts;

final class TelegramAlertFormatter
{
    /**
     * Telegram proxy currently may not forward parse_mode correctly. Keep the
     * alert readable even when Telegram receives plain text.
     *
     * @param array<string, string|int> $fields
     */
    public function format(string $title, string $description, array $fields = [], ?string $snippet = null): string
    {
        $lines = [
            '🚨 ' . $this->clean($title),
            '━━━━━━━━━━━━━━',
            '📌 ' . $this->clean($description),
        ];

        foreach ($fields as $label => $value) {
            if ((string) $value === '') {
                continue;
            }

            $lines[] = '▫️ ' . $this->clean($label) . ': ' . $this->clean((string) $value);
        }

        if ($snippet !== null && trim($snippet) !== '') {
            $lines[] = '🧩 جزئیات فنی';
            $lines[] = "```\n" . $this->redact($snippet) . "\n```";
        }

        $lines[] = '🕒 ' . now('Asia/Tehran')->format('Y-m-d H:i:s');
        $lines[] = '🌐 ' . $this->clean((string) config('app.env', 'unknown'));

        return implode("\n", $lines);
    }

    private function clean(string $value): string
    {
        return str_replace(["\r", "\n"], [' ', ' '], strip_tags($value));
    }

    private function redact(string $snippet): string
    {
        $snippet = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $snippet) ?? '';
        $snippet = preg_replace('~https?://[^\s<>]+~iu', '[URL REDACTED]', $snippet) ?? '';
        $snippet = preg_replace('~\b(?:Bearer|Client-Token|api-key|password|secret|token)\s*[:= ]\s*[^\s,;]+~iu', '[SECRET REDACTED]', $snippet) ?? '';
        $snippet = preg_replace('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[EMAIL REDACTED]', $snippet) ?? '';
        $snippet = preg_replace('/(?<!\d)09\d{9}(?!\d)/u', '[MOBILE REDACTED]', $snippet) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($snippet, 0, 950, 'UTF-8')
            : substr($snippet, 0, 950);
    }
}

<?php

namespace App\Services\Alerts;

final class TelegramAlertFormatter
{
    /**
     * Persian labels and isolated LTR technical values. The company proxy must
     * forward parse_mode=HTML for Telegram to render bold and code blocks.
     * Dynamic values are HTML-escaped; do not pass raw response bodies here.
     *
     * @param array<string, string|int> $fields
     */
    public function format(string $title, string $description, array $fields = [], ?string $snippet = null): string
    {
        $lines = [
            '🚨 <b>' . $this->escape($title) . '</b>',
            '━━━━━━━━━━━━━━',
            '📌 ' . $this->escape($description),
        ];

        foreach ($fields as $label => $value) {
            if ((string) $value === '') {
                continue;
            }

            // FSI/PDI prevent English identifiers from disrupting Persian labels.
            $lines[] = '▫️ <b>' . $this->escape($label) . '</b>  ' . "\u{2068}"
                . '<code>' . $this->escape((string) $value) . '</code>' . "\u{2069}";
        }

        if ($snippet !== null && trim($snippet) !== '') {
            $lines[] = '🧩 <b>جزئیات فنی</b>';
            $lines[] = '<pre><code>' . $this->escape($this->redact($snippet)) . '</code></pre>';
        }

        $lines[] = '🕒 <code>' . now('Asia/Tehran')->format('Y-m-d H:i:s') . '</code>';
        $lines[] = '🌐 <code>' . $this->escape((string) config('app.env', 'unknown')) . '</code>';

        // Leave margin below Telegram's message-length limit.
        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    private function redact(string $snippet): string
    {
        // Defense-in-depth: never intentionally supply raw provider payloads.
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

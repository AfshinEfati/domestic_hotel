<?php

namespace App\Services;

class HotelChildPolicyTextParser
{
    public function parse(string $text, array $conditions = []): array
    {
        $text = $this->normalize($text);

        $maxInfantAge = $this->toInt($conditions['max_infant_age'] ?? null);
        $maxChildAge = $this->toInt($conditions['max_child_age'] ?? null);

        $result = [
            'infant_pricing_type' => null,
            'infant_pricing_value' => null,
            'child_pricing_type' => null,
            'child_pricing_value' => null,
            'infant_service_condition' => null,
            'child_service_condition' => null,
            // Shared limit across free infants and discounted children.
            'max_children_covered' => $this->toNullableInt($conditions['max_children_covered'] ?? null),
            // Additional infant-only limit, inside the shared limit.
            'max_infants_covered' => $this->toNullableInt($conditions['max_infants_covered'] ?? null),
        ];

        $sharedCoverage = $this->detectSharedCoverage($text);
        $blocks = $this->splitBlocks($text);

        foreach ($blocks as $block) {
            $group = $this->detectGroup($block, $maxInfantAge, $maxChildAge);
            if (!$group) {
                continue;
            }

            $pricing = $this->detectPricing($block);
            if ($pricing) {
                $result[$group . '_pricing_type'] ??= $pricing['type'];
                $result[$group . '_pricing_value'] ??= $pricing['value'];
            }

            $service = $this->detectServiceCondition($block);
            if ($service) {
                $result[$group . '_service_condition'] ??= $service;
            }

            $coverage = $this->detectCoverage($block);
            if ($coverage === null) {
                continue;
            }

            // A statement about "one child" is not an infant-specific limit,
            // even when this hotel defines only an infant age range.
            if (str_contains($block, 'کودک')) {
                $result['max_children_covered'] ??= $coverage;
            } elseif ($group === 'infant') {
                $result['max_infants_covered'] ??= $coverage;
            } else {
                $result['max_children_covered'] ??= $coverage;
            }
        }

        // Explicit "free and half rate for only one child" wins over
        // block-level guesses; it covers both infant and child discounts.
        if ($sharedCoverage !== null) {
            $result['max_children_covered'] = $sharedCoverage;
        }

        $this->applyServiceFallback($result);

        return $result;
    }

    private function normalize(string $text): string
    {
        $text = strtr($text, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function splitBlocks(string $text): array
    {
        $parts = preg_split('/[\.!\n،]+/u', $text) ?: [];
        $blocks = [];

        foreach ($parts as $part) {
            $sub = preg_split('/\s+و\s+/u', $part) ?: [];
            foreach ($sub as $block) {
                $blocks[] = trim($block);
            }
        }

        return array_values(array_filter($blocks));
    }

    /**
     * Detect a limit explicitly stated for free AND half-rate stays.
     * Check entire sentences before splitting at "و" (and).
     */
    private function detectSharedCoverage(string $text): ?int
    {
        $sentences = preg_split('/[\.!؟\n]+/u', $text) ?: [];

        foreach ($sentences as $sentence) {
            if (
                !str_contains($sentence, 'رایگان')
                || !preg_match('/نیم[\s‌]?بها/u', $sentence)
                || !preg_match('/(?:تنها|فقط|حداکثر)/u', $sentence)
            ) {
                continue;
            }

            $coverage = $this->detectCoverage($sentence);
            if ($coverage !== null && str_contains($sentence, 'کودک')) {
                return $coverage;
            }
        }

        return null;
    }

    private function detectGroup(string $text, int $maxInfantAge, int $maxChildAge): ?string
    {
        $hasInfant = $maxInfantAge > 0;
        $hasChild = $maxChildAge > 0;

        if (preg_match('/زیر\s*(\d+)/u', $text, $matches)) {
            $age = (int) $matches[1];

            if ($hasInfant && !$hasChild) {
                return $age <= $maxInfantAge ? 'infant' : null;
            }

            if ($hasInfant && $age <= $maxInfantAge) {
                return 'infant';
            }

            if ($hasChild && $age <= $maxChildAge) {
                return 'child';
            }
        }

        if (preg_match('/بین\s*(\d+)\s*(?:الی|تا|-)\s*(\d+)/u', $text, $matches)) {
            $to = (int) $matches[2];

            if ($hasInfant && !$hasChild) {
                return $to <= $maxInfantAge ? 'infant' : null;
            }

            if ($hasInfant && $to <= $maxInfantAge) {
                return 'infant';
            }

            if ($hasChild && $to <= $maxChildAge) {
                return 'child';
            }
        }

        if ($hasInfant && !$hasChild) {
            return 'infant';
        }

        if (str_contains($text, 'نوزاد') && $hasInfant) {
            return 'infant';
        }

        if (str_contains($text, 'کودک') && $hasChild) {
            return 'child';
        }

        return null;
    }

    private function applyServiceFallback(array &$result): void
    {
        $conditions = [];

        if ($result['infant_service_condition']) {
            $conditions[] = $result['infant_service_condition'];
        }

        if ($result['child_service_condition']) {
            $conditions[] = $result['child_service_condition'];
        }

        $conditions = array_unique($conditions);

        if (count($conditions) === 1) {
            $single = $conditions[0];
            $result['infant_service_condition'] ??= $single;
            $result['child_service_condition'] ??= $single;
        }
    }

    private function detectPricing(string $text): ?array
    {
        if (preg_match('/رایگان/u', $text)) {
            return ['type' => 'free', 'value' => null];
        }

        if (preg_match('/نیم[\s‌]?بها/u', $text)) {
            return ['type' => 'half', 'value' => null];
        }

        if (preg_match('/(\d{1,3})\s*%/u', $text, $matches)) {
            return ['type' => 'percent', 'value' => (int) $matches[1]];
        }

        if (preg_match('/(\d{1,3})\s*درصد/u', $text, $matches)) {
            return ['type' => 'percent', 'value' => (int) $matches[1]];
        }

        if (preg_match('/(\d+)\s*(?:تومان|ریال)/u', $text, $matches)) {
            return ['type' => 'fixed', 'value' => (int) $matches[1]];
        }

        return null;
    }

    private function detectServiceCondition(string $text): ?string
    {
        if (preg_match('/عدم\s*استفاده\s*از\s*سرویس/u', $text)) {
            return 'no_service';
        }

        if (preg_match('/با\s*سرویس/u', $text)) {
            return 'with_service';
        }

        return null;
    }

    private function detectCoverage(string $text): ?int
    {
        if (preg_match('/(یک|1)\s*(کودک|نوزاد)/u', $text)) {
            return 1;
        }

        if (preg_match('/(دو|2)\s*(کودک|نوزاد)/u', $text)) {
            return 2;
        }

        return null;
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toNullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }
}

<?php

namespace App\Services;

class HotelChildPolicyTextParser
{
    public function parse(string $text, array $conditions = []): array
    {
        $text = $this->normalize($text);

        $maxInfantAge = $this->toInt($conditions['max_infant_age'] ?? null);
        $maxChildAge  = $this->toInt($conditions['max_child_age'] ?? null);

        $result = [
            'infant_pricing_type' => null,
            'infant_pricing_value' => null,
            'child_pricing_type' => null,
            'child_pricing_value' => null,
            'infant_service_condition' => null,
            'child_service_condition' => null,
            'max_children_covered' => null,
            'max_infants_covered' => null,
        ];

        $blocks = $this->splitBlocks($text);

        foreach ($blocks as $block) {

            $group = $this->detectGroup($block, $maxInfantAge, $maxChildAge);
            if (!$group) {
                continue;
            }

            $pricing = $this->detectPricing($block);
            if ($pricing) {
                $result[$group . '_pricing_type']  ??= $pricing['type'];
                $result[$group . '_pricing_value'] ??= $pricing['value'];
            }

            $service = $this->detectServiceCondition($block);
            if ($service) {
                $result[$group . '_service_condition'] ??= $service;
            }

            $coverage = $this->detectCoverage($block);
            if ($coverage !== null) {
                if ($group === 'infant') {
                    $result['max_infants_covered'] ??= $coverage;
                } else {
                    $result['max_children_covered'] ??= $coverage;
                }
            }
        }
        $this->applyServiceFallback($result);

        return $result;
    }

    private function normalize(string $text): string
    {
        $text = strtr($text, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        ]);

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function splitBlocks(string $text): array
    {
        $parts = preg_split('/[\.!\n،]+/u', $text) ?: [];

        $blocks = [];

        foreach ($parts as $part) {
            $sub = preg_split('/\s+و\s+/u', $part) ?: [];
            foreach ($sub as $s) {
                $blocks[] = trim($s);
            }
        }

        return array_values(array_filter($blocks));
    }

    private function detectGroup(string $text, int $maxInfantAge, int $maxChildAge): ?string
    {
        $hasInfant = $maxInfantAge > 0;
        $hasChild  = $maxChildAge > 0;

        // --- AGE BASED FIRST ---
        if (preg_match('/زیر\s*(\d+)/u', $text, $m)) {
            $age = (int)$m[1];

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

        if (preg_match('/بین\s*(\d+)\s*(?:الی|تا|-)\s*(\d+)/u', $text, $m)) {
            $to = (int)$m[2];

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

        // --- KEYWORD FALLBACK ---
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

            if ($result['infant_service_condition'] === null) {
                $result['infant_service_condition'] = $single;
            }

            if ($result['child_service_condition'] === null) {
                $result['child_service_condition'] = $single;
            }
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

        if (preg_match('/(\d{1,3})\s*%/u', $text, $m)) {
            return ['type' => 'percent', 'value' => (int)$m[1]];
        }

        if (preg_match('/(\d{1,3})\s*درصد/u', $text, $m)) {
            return ['type' => 'percent', 'value' => (int)$m[1]];
        }

        if (preg_match('/(\d+)\s*(?:تومان|ریال)/u', $text, $m)) {
            return ['type' => 'fixed', 'value' => (int)$m[1]];
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
        return is_numeric($value) ? (int)$value : 0;
    }
}

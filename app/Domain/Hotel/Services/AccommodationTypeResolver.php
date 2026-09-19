<?php

namespace App\Domain\Hotel\Services;

use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;

/** Provider names map only to the seeded taxonomy; unknowns share one row. */
class AccommodationTypeResolver
{
    public const CANONICAL_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];

    /** Keys are normalized by normalize(), including snake_case and hyphens. */
    private const ALIASES = [
        'اقامتگاه سنتی' => 1,
        'traditional residence' => 1,
        'traditional accommodation' => 1,
        'خانه مسافر' => 2,
        'traveler house' => 2,
        'traveller house' => 2,
        'اقامتگاه بوم گردی' => 3,
        'ecotourism resorts' => 3,
        'ecotourism resort' => 3,
        'eco tourism resort' => 3,
        'eco tourism resorts' => 3,
        'هتل' => 4,
        'hotel' => 4,
        'هتل آپارتمان' => 5,
        'apartment hotel' => 5,
        'hotel apartment' => 5,
        'aparthotel' => 5,
        'مجتمع اقامتی' => 6,
        'residential complex' => 6,
        'مجتمع گردشگری' => 7,
        'tourism complex' => 7,
        'tourist complex' => 7,
        'مهمانسرای ممتاز' => 8,
        'privilege inn' => 8,
        'مهمانسرا' => 9,
        'inn' => 9,
        'هتل بوتیک' => 10,
        'boutique' => 10,
        'boutique hotel' => 10,
        'متل' => 11,
        'motel' => 11,
        'سوئیت آپارتمانی' => 12,
        'apartment suite' => 12,
        'suite apartment' => 12,
        'هاستل' => 13,
        'hostel' => 13,
        'مجتمع اقامتی ساحلی' => 14,
        'beach residential complex' => 14,
        'beachside residential complex' => 14,
        'واحد اقامتی' => 15,
        'residential unit' => 15,
        'پانسیون' => 16,
        'pension' => 16,
    ];

    /** @var array<int, int|null> */
    private array $resolvedCanonicalIds = [];
    private ?int $unknownId = null;

    public function __construct(private readonly AccommodationTypeRepositoryInterface $types)
    {
    }

    /** Never guess by substring; conflicting recognized names mean unknown. */
    public function canonicalId(mixed $providerType, mixed $providerEnglishType = null): ?int
    {
        $primary = self::ALIASES[$this->normalize($providerType)] ?? null;
        $secondary = self::ALIASES[$this->normalize($providerEnglishType)] ?? null;

        if ($primary !== null && $secondary !== null && $primary !== $secondary) {
            return null;
        }

        return $primary ?? $secondary;
    }

    public function resolveId(mixed $providerType, mixed $providerEnglishType = null): int
    {
        $id = $this->canonicalId($providerType, $providerEnglishType);
        if ($id !== null) {
            if (!array_key_exists($id, $this->resolvedCanonicalIds)) {
                // Resolve the seeded ID from the database; never create canonical types.
                $this->resolvedCanonicalIds[$id] = $this->types->find($id)?->id;
            }
            if ($this->resolvedCanonicalIds[$id] !== null) {
                return $this->resolvedCanonicalIds[$id];
            }
        }

        return $this->unknownId ??= $this->types->getOrCreateUnknownType()->id;
    }

    private function normalize(mixed $value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        $value = trim((string) $value);
        $value = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $value) ?? $value;
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة', '‌', 'ـ'], ['ی', 'ی', 'ک', 'ه', 'ه', ' ', ''], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}

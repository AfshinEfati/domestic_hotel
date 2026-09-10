<?php

namespace App\Services;

use App\DTOs\HotelSettingDTO;
use App\Models\HotelSetting;
use App\Repositories\Contracts\HotelSettingRepositoryInterface;
use App\Services\Contracts\HotelSettingServiceInterface;
use App\Support\Hotel\HotelSettingValueType;
use JsonException;
use RuntimeException;

class HotelSettingService extends BaseService implements HotelSettingServiceInterface
{
    /** @var array<string, mixed> */
    private array $resolvedValues = [];

    public function __construct(HotelSettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function store(mixed $payload): HotelSetting
    {
        $data = $payload instanceof HotelSettingDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        $data = $this->prepareForStorage($data);

        /** @var HotelSetting $setting */
        $setting = parent::store($data);
        $this->resolvedValues = [];

        return $setting;
    }

    public function update(int|string $id, mixed $payload): bool
    {
        $data = $payload instanceof HotelSettingDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        if (array_key_exists('value', $data) && !array_key_exists('value_type', $data)) {
            /** @var HotelSetting|null $setting */
            $setting = $this->repository->find($id);
            if ($setting === null) {
                return false;
            }
            $data['value_type'] = $setting->value_type;
        }

        $data = $this->prepareForStorage($data);
        $updated = parent::update($id, $data);

        if ($updated) {
            $this->resolvedValues = [];
        }

        return $updated;
    }

    public function getValue(string $key): mixed
    {
        if (array_key_exists($key, $this->resolvedValues)) {
            return $this->resolvedValues[$key];
        }

        /** @var HotelSettingRepositoryInterface $repository */
        $repository = $this->repository;
        $setting = $repository->findActiveByKey($key);

        if ($setting === null) {
            throw new RuntimeException(sprintf('Required hotel setting [%s] was not found or is inactive.', $key));
        }

        return $this->resolvedValues[$key] = $this->castValue(
            $setting->value,
            $setting->value_type
        );
    }

    private function prepareForStorage(array $data): array
    {
        if (!array_key_exists('value', $data)) {
            return $data;
        }

        $valueType = (string) ($data['value_type'] ?? '');

        if (!HotelSettingValueType::isValid($valueType)) {
            throw new RuntimeException('Invalid hotel setting value type.');
        }

        $data['value'] = $this->serializeValue($data['value'], $valueType);

        return $data;
    }

    private function serializeValue(mixed $value, string $valueType): string
    {
        return match ($valueType) {
            HotelSettingValueType::STRING => (string) $value,
            HotelSettingValueType::INTEGER => (string) (int) $value,
            HotelSettingValueType::FLOAT => (string) (float) $value,
            HotelSettingValueType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            HotelSettingValueType::JSON => $this->encodeJson($value),
            default => throw new RuntimeException('Unsupported hotel setting value type.'),
        };
    }

    private function castValue(string $value, string $valueType): mixed
    {
        return match ($valueType) {
            HotelSettingValueType::STRING => $value,
            HotelSettingValueType::INTEGER => (int) $value,
            HotelSettingValueType::FLOAT => (float) $value,
            HotelSettingValueType::BOOLEAN => in_array(strtolower($value), ['1', 'true'], true),
            HotelSettingValueType::JSON => $this->decodeJson($value),
            default => throw new RuntimeException('Unsupported hotel setting value type.'),
        };
    }

    private function encodeJson(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON hotel setting value.', 0, $exception);
        }
    }

    private function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON stored in hotel setting.', 0, $exception);
        }
    }
}

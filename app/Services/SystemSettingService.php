<?php

namespace App\Services;

use App\DTOs\SystemSettingDTO;
use App\Models\SystemSetting;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;
use App\Services\Contracts\SystemSettingServiceInterface;
use App\Support\System\SystemSettingValueType;
use JsonException;
use RuntimeException;

class SystemSettingService extends BaseService implements SystemSettingServiceInterface
{
    /** @var array<string, mixed> */
    private array $resolvedValues = [];

    public function __construct(
        private readonly SystemSettingRepositoryInterface $systemSettingRepository
    ) {
        parent::__construct($systemSettingRepository);
    }

    public function store(mixed $payload): SystemSetting
    {
        $data = $payload instanceof SystemSettingDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        $data = $this->prepareForStorage($data);

        /** @var SystemSetting $setting */
        $setting = parent::store($data);
        $this->resolvedValues = [];

        return $setting;
    }

    public function update(int|string $id, mixed $payload): bool
    {
        $data = $payload instanceof SystemSettingDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        $setting = $this->systemSettingRepository->find($id);
        if ($setting === null) {
            return false;
        }

        if (array_key_exists('value', $data) && !array_key_exists('value_type', $data)) {
            $data['value_type'] = $setting->value_type;
        }

        if (array_key_exists('value_type', $data) && !array_key_exists('value', $data)) {
            $data['value'] = $setting->value;
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

        $setting = $this->systemSettingRepository->findActiveByKey($key);

        if ($setting === null) {
            throw new RuntimeException(sprintf('Required system setting [%s] was not found or is inactive.', $key));
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

        if (!SystemSettingValueType::isValid($valueType)) {
            throw new RuntimeException('Invalid system setting value type.');
        }

        $data['value'] = $this->serializeValue($data['value'], $valueType);

        return $data;
    }

    private function serializeValue(mixed $value, string $valueType): string
    {
        return match ($valueType) {
            SystemSettingValueType::STRING => (string) $value,
            SystemSettingValueType::INTEGER => (string) (int) $value,
            SystemSettingValueType::FLOAT => (string) (float) $value,
            SystemSettingValueType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            SystemSettingValueType::JSON => $this->encodeJson($value),
            default => throw new RuntimeException('Unsupported system setting value type.'),
        };
    }

    private function castValue(string $value, string $valueType): mixed
    {
        return match ($valueType) {
            SystemSettingValueType::STRING => $value,
            SystemSettingValueType::INTEGER => (int) $value,
            SystemSettingValueType::FLOAT => (float) $value,
            SystemSettingValueType::BOOLEAN => in_array(strtolower($value), ['1', 'true'], true),
            SystemSettingValueType::JSON => $this->decodeJson($value),
            default => throw new RuntimeException('Unsupported system setting value type.'),
        };
    }

    private function encodeJson(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON system setting value.', 0, $exception);
        }
    }

    private function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON stored in system setting.', 0, $exception);
        }
    }
}

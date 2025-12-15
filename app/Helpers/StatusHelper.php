<?php

namespace App\Helpers;

use DateTimeInterface;
use Efati\ModuleGenerator\Support\Goli;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Throwable;

class StatusHelper
{
    public static function successResponse(mixed $data = null, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public static function errorResponse(string $message = 'error', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    public static function unauthorized(string $message = 'unauthorized'): JsonResponse
    {
        return self::errorResponse($message, 401);
    }

    public static function forbidden(string $message = 'forbidden'): JsonResponse
    {
        return self::errorResponse($message, 403);
    }

    public static function notFound(string $message = 'not found'): JsonResponse
    {
        return self::errorResponse($message, 404);
    }

    /**
     * Format date fields into a consistent structure using the built-in Goli helper.
     * Falls back gracefully if parsing fails.
     */
    public static function formatDates(
        Carbon|DateTimeInterface|Goli|int|string|null $datetime
    ): ?array {
        if ($datetime === null) {
            return null;
        }

        if (is_string($datetime) && trim($datetime) === '') {
            return null;
        }

        try {
            $jalali = $datetime instanceof Goli ? $datetime : Goli::instance($datetime);
            $carbon = $jalali->toCarbon();
        } catch (Throwable) {
            try {
                if ($datetime instanceof Carbon) {
                    $carbon = $datetime->copy();
                } elseif ($datetime instanceof DateTimeInterface) {
                    $carbon = Carbon::instance($datetime);
                } elseif (is_int($datetime)) {
                    $carbon = Carbon::createFromTimestamp($datetime);
                } else {
                    $carbon = Carbon::parse((string) $datetime);
                }

                $jalali = Goli::instance($carbon);
            } catch (Throwable) {
                return null;
            }
        }

        return [
            'date'    => $carbon->toDateString(),
            'time'    => $carbon->toTimeString(),
            'fa_date' => $jalali->format('Y-m-d'),
            'iso'     => $carbon->toIso8601String(),
        ];
    }

    /**
     * Return normalized status object for booleans.
     */
    public static function getStatus(bool $value): array
    {
        return [
            'name'    => $value ? 'active' : 'inactive',
            'fa_name' => $value ? 'فعال' : 'غیرفعال',
            'code'    => $value ? 1 : 0,
        ];
    }

}

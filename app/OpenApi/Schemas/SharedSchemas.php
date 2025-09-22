<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Shared reusable schema definitions for the Domestic Hotel API.
 *
 * @OA\Schema(
 *     schema="Status",
 *     title="Status",
 *     description="Normalized status object used throughout the API.",
 *     type="object",
 *     @OA\Property(property="name", type="string", example="active"),
 *     @OA\Property(property="fa_name", type="string", example="فعال"),
 *     @OA\Property(property="code", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="Timestamp",
 *     title="Timestamp",
 *     description="Standardized timestamp representation.",
 *     type="object",
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-20"),
 *     @OA\Property(property="time", type="string", format="time", example="14:32:00"),
 *     @OA\Property(property="fa_date", type="string", example="1402-11-01"),
 *     @OA\Property(property="iso", type="string", format="date-time", example="2024-01-20T14:32:00+00:00")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     title="Success response",
 *     description="Envelope returned on successful requests.",
 *     type="object",
 *     required={"success", "message"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="success")
 * )
 *
 * @OA\Schema(
 *     schema="EmptySuccessResponse",
 *     title="Empty success response",
 *     description="Envelope returned when no payload is included in the response body.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(property="data", type="object", nullable=true, example=null)
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error response",
 *     description="Generic error envelope returned by the API.",
 *     type="object",
 *     required={"success", "message"},
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
class SharedSchemas
{
}

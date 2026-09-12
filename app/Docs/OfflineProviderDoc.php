<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Provider")
 */
class OfflineProviderDoc
{
    /**
     * @OA\Post(
     *     path="/api/v1/admin/providers/offline",
     *     summary="Create or refresh an offline provider from an accommodation",
     *     tags={"Provider"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"accommodation_id"},
     *             @OA\Property(property="accommodation_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Offline provider created or refreshed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="fa_name", type="string", example="Hotel Name"),
     *             @OA\Property(property="en_name", type="string", nullable=true, example="Hotel Name"),
     *             @OA\Property(property="code", type="string", example="hotel-123"),
     *             @OA\Property(property="is_active", type="object"),
     *             @OA\Property(property="is_online", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function postApiV1AdminProvidersOffline(): void
    {
    }
}

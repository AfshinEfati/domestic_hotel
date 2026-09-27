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
     *     summary="Create or refresh a direct hotel provider",
     *     description="Creates an accounting/procurement-only provider for direct purchase from the hotel. The provider is linked to accommodation_id, has provider_type=hotel_direct and is_online=false, and must not participate in rate, capacity, availability or online booking flows.",
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
     *             @OA\Property(property="is_online", type="object"),
     *             @OA\Property(
     *                 property="provider_type",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="hotel_direct"),
     *                 @OA\Property(property="fa_name", type="string", example="خرید مستقیم از هتل"),
     *                 @OA\Property(property="code", type="integer", example=2)
     *             ),
     *             @OA\Property(property="accommodation_id", type="integer", example=123)
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

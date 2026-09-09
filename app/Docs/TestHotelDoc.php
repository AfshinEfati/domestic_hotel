<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="TestHotel")
 */
class TestHotelDoc
{
    /**
     * @OA\Get(
     *     path="/api/v1/test-hotel",
     *     summary="Test",
     *     tags={"TestHotel"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     * )
     */
    public function getApiV1TestHotel(){}
}

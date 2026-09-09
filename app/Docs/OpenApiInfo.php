<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Laravel",
 *         description="API Documentation",
 *         @OA\Contact(
 *             name="API Support"
 *         )
 *     ),
 *     @OA\Server(
 *         url="http://domestic-hotel.local",
 *         description="Development Server"
 *     ),
 *     @OA\Server(
 *         url="https://newhotel.shahansafar.ir",
 *         description="Production Server"
 *     )
 * )
 */
class OpenApiInfo
{
}

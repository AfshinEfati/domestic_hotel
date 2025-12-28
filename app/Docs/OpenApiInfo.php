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
 *         url="http://dhotel.local",
 *         description="Development Server"
 *     ),
 *     @OA\Server(
 *         url="https://api.example.com",
 *         description="Production Server"
 *     )
 * )
 */
class OpenApiInfo
{
}

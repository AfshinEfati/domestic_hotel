<?php

namespace App\OpenApi\SecuritySchemes;

use OpenApi\Annotations as OA;

/**
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum Token",
 *     description="Authenticate using a Laravel Sanctum personal access token in the Authorization header."
 * )
 */
class SanctumSecurityScheme
{
}

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The provider request succeeded, but the returned payload is incomplete or
 * inconsistent for this resource. Callers may skip that resource safely.
 */
final class ProviderDataException extends RuntimeException
{
}

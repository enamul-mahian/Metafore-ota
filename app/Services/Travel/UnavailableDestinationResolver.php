<?php

namespace App\Services\Travel;

use App\Contracts\Travel\DestinationResolver;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class UnavailableDestinationResolver implements DestinationResolver
{
    public function resolve(string $destination): array
    {
        throw new ServiceUnavailableHttpException(
            null,
            'Hotel destination resolver is not configured.'
        );
    }
}

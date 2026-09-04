<?php

namespace App\Services\Visa;

use App\Contracts\Visa\VisaInformationProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UnavailableVisaInformationProvider implements VisaInformationProvider
{
    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function requirements(array $criteria): array
    {
        throw new ServiceUnavailableHttpException(
            60,
            'Visa information service is not configured.'
        );
    }
}

<?php

namespace App\Contracts\Visa;

interface VisaInformationProvider
{
    /**
     * Return provider-neutral visa requirement information.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function requirements(array $criteria): array;
}

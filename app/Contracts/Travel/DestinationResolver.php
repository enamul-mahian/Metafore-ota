<?php

namespace App\Contracts\Travel;

interface DestinationResolver
{
    /**
     * @return array{
     *     name: string,
     *     latitude: float,
     *     longitude: float
     * }
     */
    public function resolve(string $destination): array;
}

<?php

namespace App\Contracts\Tour;

interface TourSearchProvider
{
    /**
     * Search tour inventory using provider-neutral criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array;
}

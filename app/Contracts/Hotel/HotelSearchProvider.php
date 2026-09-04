<?php

namespace App\Contracts\Hotel;

interface HotelSearchProvider
{
    /**
     * Search hotel inventory using provider-neutral criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria): array;
}

<?php

namespace Tests\Unit;

use App\Models\Location;
use Tests\TestCase;

class LocationDisplayTest extends TestCase
{
    public function test_locality_from_address_prefers_city_segment(): void
    {
        $location = new Location([
            'address' => 'Quezon Blvd, Kidapawan City, North Cotabato',
        ]);

        $this->assertSame('Kidapawan City', $location->localityFromAddress());
    }

    public function test_locality_from_address_returns_municipality_before_province(): void
    {
        $location = new Location([
            'address' => 'Poblacion, Carmen, North Cotabato',
        ]);

        $this->assertSame('Carmen', $location->localityFromAddress());
    }

    public function test_guard_display_fields_use_client_and_address(): void
    {
        $location = new Location([
            'name' => 'Carmen Checkpoint',
            'client_name' => 'Carmen Municipal Hall',
            'address' => 'Poblacion, Carmen, North Cotabato',
        ]);

        $this->assertSame([
            'post' => 'Carmen Checkpoint',
            'client' => 'Carmen Municipal Hall',
            'area' => 'Carmen Municipal Hall',
            'city' => 'Carmen',
            'address' => 'Poblacion, Carmen, North Cotabato',
            'location_subtitle' => 'Carmen Municipal Hall · Carmen',
        ], $location->guardDisplayFields());
    }
}

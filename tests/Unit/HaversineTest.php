<?php

namespace Tests\Unit;

use App\Support\Haversine;
use PHPUnit\Framework\TestCase;

class HaversineTest extends TestCase
{
    public function test_one_degree_of_longitude_on_the_equator(): void
    {
        $this->assertEqualsWithDelta(111195, Haversine::meters([0, 0], [1, 0]), 1);
    }

    public function test_sao_paulo_to_rio_de_janeiro(): void
    {
        $meters = Haversine::meters([-46.6333, -23.5505], [-43.1729, -22.9068]);

        $this->assertEqualsWithDelta(360750, $meters, 100);
    }

    public function test_path_sums_consecutive_legs(): void
    {
        $path = [[0, 0], [1, 0], [0, 0]];

        $this->assertEqualsWithDelta(2 * Haversine::meters([0, 0], [1, 0]), Haversine::pathMeters($path), 0.001);
    }

    public function test_path_with_a_single_point_is_zero(): void
    {
        $this->assertSame(0.0, Haversine::pathMeters([[-46.6333, -23.5505]]));
    }
}

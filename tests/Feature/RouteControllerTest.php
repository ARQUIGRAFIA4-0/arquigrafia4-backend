<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    private const SE = [-46.6333, -23.5505];

    private const RIO = [-43.1729, -22.9068];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.percursos.max_distance_km' => 50.0]);

        // Usuário não persistido: a rota só precisa de alguém autenticado e não toca o banco.
        Passport::actingAs((new User())->forceFill(['id' => 1, 'name' => 'Teste']));
    }

    private function fakeOsrm(float $distanceMeters): void
    {
        Http::fake([
            '*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => $distanceMeters, 'geometry' => ['type' => 'LineString', 'coordinates' => []]]],
            ]),
        ]);
    }

    public function test_rejects_stops_too_far_apart_without_calling_osrm(): void
    {
        $this->fakeOsrm(400000);

        $response = $this->postJson('/api/route', ['coordinates' => [self::SE, self::RIO]]);

        $response->assertStatus(422)->assertJsonPath('code', 'TooLong');
        $this->assertEquals(50000, $response->json('maxDistanceMeters'));
        $this->assertGreaterThan(350000, $response->json('distanceMeters'));
        Http::assertNothingSent();
    }

    public function test_sums_every_leg_even_when_the_ends_are_close(): void
    {
        $this->fakeOsrm(1000);

        // Vai e volta 0,2° de latitude (~22 km por perna): as pontas ficam a ~22 km, mas o
        // percurso tem ~67 km em linha reta.
        $north = [self::SE[0], self::SE[1] + 0.2];

        $response = $this->postJson('/api/route', ['coordinates' => [self::SE, $north, self::SE, $north]]);

        $response->assertStatus(422)->assertJsonPath('code', 'TooLong');
        Http::assertNothingSent();
    }

    public function test_short_route_is_sent_to_osrm(): void
    {
        $this->fakeOsrm(1200);

        $response = $this->postJson('/api/route', ['coordinates' => [self::SE, [-46.6400, -23.5450]]]);

        $response->assertOk()->assertJsonPath('code', 'Ok');
        Http::assertSentCount(1);
    }

    public function test_still_rejects_when_the_walking_route_exceeds_the_limit(): void
    {
        // ~40 km em linha reta passa na pré-checagem; a rota a pé de 60 km não.
        $this->fakeOsrm(60000);

        $response = $this->postJson('/api/route', ['coordinates' => [self::SE, [self::SE[0], self::SE[1] + 0.36]]]);

        $response->assertStatus(422)->assertJsonPath('code', 'TooLong');
        $this->assertEquals(60000, $response->json('distanceMeters'));
        Http::assertSentCount(1);
    }
}

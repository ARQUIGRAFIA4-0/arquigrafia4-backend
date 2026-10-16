<?php

namespace Tests\Feature;

use App\Console\Commands\TransformLegacy;
use App\Models\Album;
use App\Models\Collective;
use App\Models\User;
use App\Models\VRACore\VRACImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyAlbumsTest extends TestCase
{
    use RefreshDatabase;

    /** TransformLegacy::COLLECTIVE_MAP['1'] — Acervo da Biblioteca da FAUUSP. */
    private const LIBRARY_COLLECTIVE_UUID = 'a0d427a6-b8d8-42db-a9d9-4fc02ae8c4c5';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy_dump' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('legacy_dump');

        $schema = Schema::connection('legacy_dump');
        $schema->create('albums', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->dateTime('creationDate')->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cover_id')->nullable();
            $table->unsignedBigInteger('institution_id')->nullable();
        });
        $schema->create('album_elements', function (Blueprint $table) {
            $table->unsignedBigInteger('album_id');
            $table->unsignedBigInteger('photo_id');
        });
    }

    private function user(int $legacyId): User
    {
        return User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'legacy_id' => $legacyId,
        ]);
    }

    private function image(User $owner, int $legacyId): VRACImage
    {
        $image = VRACImage::create(['user_id' => $owner->id]);
        $image->forceFill(['legacy_id' => $legacyId])->save();

        return $image;
    }

    private function legacyAlbum(int $id, string $title, ?int $userId, array $photoIds = [], ?int $institutionId = null, ?string $date = '2020-05-01 10:00:00', ?string $description = null): void
    {
        DB::connection('legacy_dump')->table('albums')->insert([
            'id' => $id,
            'creationDate' => $date,
            'description' => $description,
            'title' => $title,
            'user_id' => $userId,
            'institution_id' => $institutionId,
        ]);

        foreach ($photoIds as $photoId) {
            DB::connection('legacy_dump')->table('album_elements')->insert([
                'album_id' => $id,
                'photo_id' => $photoId,
            ]);
        }
    }

    private function import(bool $dryRun = false): array
    {
        return app(TransformLegacy::class)->importLegacyAlbums($dryRun);
    }

    public function test_imports_a_public_album_with_its_photos_in_order(): void
    {
        $owner = $this->user(36);
        $first = $this->image($owner, 100);
        $second = $this->image($owner, 101);
        $this->legacyAlbum(428, 'Coleção Ruth Verde Zein', 36, [101, 100], description: 'Acervo');

        $stats = $this->import();

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(2, $stats['links']);

        $album = Album::where('legacy_id', 428)->firstOrFail();
        $this->assertSame($owner->id, $album->user_id);
        $this->assertSame('Coleção Ruth Verde Zein', $album->title);
        $this->assertSame('Acervo', $album->description);
        $this->assertFalse($album->is_private);
        $this->assertSame('2020-05-01 10:00:00', $album->created_at->format('Y-m-d H:i:s'));
        $this->assertSame(
            [$second->id, $first->id],
            $album->images()->orderBy('album_image.position')->pluck('vrac_images.id')->all()
        );
    }

    public function test_institution_albums_get_the_mapped_collective(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $collective = Collective::create(['id' => self::LIBRARY_COLLECTIVE_UUID, 'name' => 'Acervo da Biblioteca da FAUUSP']);
        $this->legacyAlbum(428, 'Coleção Ruth Verde Zein', 36, [100], institutionId: 1);

        $stats = $this->import();

        $this->assertSame(1, $stats['collective']);
        $this->assertSame($collective->id, Album::where('legacy_id', 428)->value('collective_id'));
    }

    public function test_album_is_imported_without_collective_when_it_does_not_exist_locally(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(428, 'Coleção Ruth Verde Zein', 36, [100], institutionId: 1);

        $stats = $this->import();

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['collective_missing']);
        $this->assertNull(Album::where('legacy_id', 428)->value('collective_id'));
    }

    public function test_skips_favorites_empty_albums_and_unknown_owners(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(1, 'Favoritos', 36, [100]);
        $this->legacyAlbum(2, 'Vazio', 36, []);
        $this->legacyAlbum(3, 'Sem dono', 999, [100]);
        $this->legacyAlbum(4, 'Bom', 36, [100]);

        $stats = $this->import();

        $this->assertSame(1, $stats['favorites']);
        $this->assertSame(1, $stats['no_photos']);
        $this->assertSame(1, $stats['no_owner']);
        $this->assertSame(1, $stats['imported']);
        $this->assertSame([4], Album::pluck('legacy_id')->all());
    }

    public function test_drops_photos_missing_in_the_new_system_but_keeps_the_album(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(10, 'Parcial', 36, [100, 555]);
        $this->legacyAlbum(11, 'Só fotos perdidas', 36, [555, 556]);

        $stats = $this->import();

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['links']);
        $this->assertSame(3, $stats['links_missing']);
        $this->assertSame(1, $stats['no_photos']);
        $this->assertSame([10], Album::pluck('legacy_id')->all());
    }

    public function test_running_twice_does_not_duplicate_albums(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(10, 'Uma vez', 36, [100]);

        $this->import();
        $second = $this->import();

        $this->assertSame(1, $second['already']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, Album::count());
        $this->assertSame(1, DB::table('album_image')->count());
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(10, 'Simulado', 36, [100]);

        $stats = $this->import(dryRun: true);

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(0, Album::count());
        $this->assertSame(0, DB::table('album_image')->count());
    }

    public function test_legacy_id_is_not_exposed_in_the_api(): void
    {
        $owner = $this->user(36);
        $this->image($owner, 100);
        $this->legacyAlbum(10, 'Pública', 36, [100]);
        $this->import();

        $response = $this->getJson('/api/albums');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.title', 'Pública');
        $this->assertArrayNotHasKey('legacy_id', $response->json('data.0'));
    }
}

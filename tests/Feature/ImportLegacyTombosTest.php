<?php

namespace Tests\Feature;

use App\Console\Commands\TransformLegacy;
use App\Models\User;
use App\Models\VRACore\VRACAgent;
use App\Models\VRACore\VRACAgentRole;
use App\Models\VRACore\VRACContributorName;
use App\Models\VRACore\VRACImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportLegacyTombosTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy_dump' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('legacy_dump');

        Schema::connection('legacy_dump')->create('photos', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('tombo')->nullable();
            $table->unsignedBigInteger('institution_id')->nullable();
        });

        $this->owner = User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);
    }

    private function image(int $legacyId): VRACImage
    {
        $image = VRACImage::create(['user_id' => $this->owner->id]);
        $image->forceFill(['legacy_id' => $legacyId])->save();

        return $image;
    }

    private function legacyPhoto(int $id, ?string $tombo, ?int $institutionId = 1): void
    {
        DB::connection('legacy_dump')->table('photos')->insert([
            'id' => $id,
            'tombo' => $tombo,
            'institution_id' => $institutionId,
        ]);
    }

    private function tombo(VRACImage $image): ?string
    {
        return DB::table('vrac_images')->where('id', $image->id)->value('tombo');
    }

    private function import(bool $dryRun = false): array
    {
        return app(TransformLegacy::class)->importLegacyTombos($dryRun);
    }

    public function test_copies_the_tombo_to_the_matching_image_trimmed(): void
    {
        $image = $this->image(10);
        $this->legacyPhoto(10, '  9A8148 ');

        $stats = $this->import();

        $this->assertSame(1, $stats['updated']);
        $this->assertSame('9A8148', $this->tombo($image));
    }

    public function test_skips_placeholder_and_empty_values(): void
    {
        foreach (['-', '0', '001', 'NULL', 'null', '   ', ''] as $i => $value) {
            $this->image(100 + $i);
            $this->legacyPhoto(100 + $i, $value);
        }

        $stats = $this->import();

        $this->assertSame(0, $stats['updated']);
        $this->assertSame(5, $stats['placeholder']);
        $this->assertSame(0, DB::table('vrac_images')->whereNotNull('tombo')->count());
    }

    public function test_odd_values_are_kept_as_they_are(): void
    {
        $contact = $this->image(20);
        $code = $this->image(21);
        $this->legacyPhoto(20, 'Contato 01');
        $this->legacyPhoto(21, 'FAr78/728.3ob');

        $this->import();

        $this->assertSame('Contato 01', $this->tombo($contact));
        $this->assertSame('FAr78/728.3ob', $this->tombo($code));
    }

    public function test_institution_two_is_excluded_but_others_and_no_institution_are_kept(): void
    {
        $quapa = $this->image(30);
        $library = $this->image(31);
        $none = $this->image(32);
        $this->legacyPhoto(30, 'SP14_LME_jul97_088', 2);
        $this->legacyPhoto(31, '37331', 1);
        $this->legacyPhoto(32, '6180', null);

        $stats = $this->import();

        $this->assertSame(1, $stats['excluded']);
        $this->assertNull($this->tombo($quapa));
        $this->assertSame('37331', $this->tombo($library));
        $this->assertSame('6180', $this->tombo($none));
    }

    public function test_soft_deleted_images_also_get_their_tombo(): void
    {
        $image = $this->image(40);
        $image->delete();
        $this->legacyPhoto(40, '55555');

        $stats = $this->import();

        $this->assertSame(1, $stats['updated']);
        $this->assertSame('55555', DB::table('vrac_images')->where('id', $image->id)->value('tombo'));
    }

    public function test_photo_without_a_matching_image_is_skipped(): void
    {
        $this->legacyPhoto(50, '12345');

        $stats = $this->import();

        $this->assertSame(1, $stats['no_image']);
        $this->assertSame(0, $stats['updated']);
    }

    public function test_an_existing_tombo_is_never_overwritten(): void
    {
        $image = $this->image(60);
        DB::table('vrac_images')->where('id', $image->id)->update(['tombo' => 'MANUAL-1']);
        $this->legacyPhoto(60, '99999');

        $stats = $this->import();

        $this->assertSame(1, $stats['already']);
        $this->assertSame('MANUAL-1', $this->tombo($image));
    }

    public function test_several_images_can_share_the_same_tombo(): void
    {
        $a = $this->image(70);
        $b = $this->image(71);
        $this->legacyPhoto(70, '9A8148');
        $this->legacyPhoto(71, '9A8148');

        $stats = $this->import();

        $this->assertSame(2, $stats['updated']);
        $this->assertSame('9A8148', $this->tombo($a));
        $this->assertSame('9A8148', $this->tombo($b));
    }

    public function test_running_twice_changes_nothing_the_second_time(): void
    {
        $image = $this->image(80);
        $this->legacyPhoto(80, '77777');

        $this->import();
        $second = $this->import();

        $this->assertSame(0, $second['updated']);
        $this->assertSame(1, $second['already']);
        $this->assertSame('77777', $this->tombo($image));
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $image = $this->image(90);
        $this->legacyPhoto(90, '88888');

        $stats = $this->import(dryRun: true);

        $this->assertSame(1, $stats['updated']);
        $this->assertNull($this->tombo($image));
    }

    public function test_agents_and_the_rest_of_the_image_data_are_untouched(): void
    {
        $image = $this->image(110);
        $role = VRACAgentRole::firstOrCreate(['label' => 'fotógrafo'], ['id' => (string) Str::uuid()]);
        $contributor = VRACContributorName::create(['id' => (string) Str::uuid(), 'name' => 'Fulano de Tal', 'type' => 'personal']);
        $agent = VRACAgent::create(['id' => (string) Str::uuid(), 'contributor_name_id' => $contributor->id, 'role_id' => $role->id]);
        $image->agents()->attach($agent->id);
        $this->legacyPhoto(110, '31269');

        $before = [
            'agents' => DB::table('vrac_agents')->count(),
            'contributors' => DB::table('vrac_contributor_names')->count(),
            'agent_image' => DB::table('agent_image')->count(),
            'images' => DB::table('vrac_images')->count(),
            'users' => DB::table('users')->count(),
        ];
        $updatedAt = DB::table('vrac_images')->where('id', $image->id)->value('updated_at');

        $stats = $this->import();

        $this->assertSame(1, $stats['updated']);
        $this->assertSame($before, [
            'agents' => DB::table('vrac_agents')->count(),
            'contributors' => DB::table('vrac_contributor_names')->count(),
            'agent_image' => DB::table('agent_image')->count(),
            'images' => DB::table('vrac_images')->count(),
            'users' => DB::table('users')->count(),
        ]);
        $this->assertSame($updatedAt, DB::table('vrac_images')->where('id', $image->id)->value('updated_at'));
    }

    public function test_the_tombo_is_not_exposed_in_the_model_json(): void
    {
        $image = $this->image(120);
        DB::table('vrac_images')->where('id', $image->id)->update(['tombo' => '12345']);

        $this->assertArrayNotHasKey('tombo', VRACImage::find($image->id)->toArray());
    }
}

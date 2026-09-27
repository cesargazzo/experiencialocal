<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaVariants;
use App\Livewire\ProfilePhoto;
use App\Models\Experience;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
    }

    #[Test]
    public function the_original_stays_private_and_each_variant_is_an_exact_size_webp(): void
    {
        $experience = Experience::factory()->create();

        $media = app(ImageService::class)->replace($experience, 'cover', UploadedFile::fake()->image('patio.jpg', 3000, 2000))->refresh();
        $this->assertSame('ready', $media->status);

        Storage::disk('local')->assertExists($media->original_path);
        Storage::disk('public')->assertMissing($media->original_path);
        $this->assertSame([3000, 2000], [$media->width, $media->height]);

        foreach (['card' => [800, 600, 'webp'], 'hero' => [1920, 1080, 'webp'], 'og' => [1200, 630, 'jpg']] as $name => [$width, $height, $format]) {
            $path = $media->variants[$name]['path'];
            Storage::disk('public')->assertExists($path);
            $binary = Storage::disk('public')->get($path);
            $format === 'webp'
                ? $this->assertSame('WEBP', substr($binary, 8, 4))
                : $this->assertSame("\xFF\xD8", substr($binary, 0, 2));
            $this->assertStringNotContainsString('EXIF', $binary, 'Las versiones públicas no llevan metadatos.');
            [$actualWidth, $actualHeight] = getimagesizefromstring($binary);
            $this->assertSame([$width, $height], [$actualWidth, $actualHeight]);
        }

        $this->assertStringContainsString('/storage/media/cover/', $experience->fresh()->coverUrl('card'));
    }

    #[Test]
    public function replacing_a_photo_deletes_the_previous_files(): void
    {
        $user = User::factory()->create();
        $images = app(ImageService::class);

        $first = $images->replace($user, 'avatar', UploadedFile::fake()->image('a.png', 400, 400))->refresh();
        $second = $images->replace($user, 'avatar', UploadedFile::fake()->image('b.png', 400, 400))->refresh();

        $this->assertModelMissing($first);
        Storage::disk('local')->assertMissing($first->original_path);
        Storage::disk('public')->assertMissing($first->variants['md']['path']);
        Storage::disk('public')->assertExists($second->variants['md']['path']);
        $this->assertTrue($user->fresh()->avatar->is($second));
    }

    #[Test]
    public function anyone_can_upload_a_profile_photo_from_their_account(): void
    {
        $user = User::factory()->create();

        // Con la cola sincrónica de los tests, la foto queda lista enseguida y la página se recarga.
        Livewire::actingAs($user)->test(ProfilePhoto::class)
            ->set('photo', UploadedFile::fake()->image('yo.jpg', 600, 800))
            ->assertHasNoErrors()
            ->assertRedirect(route('cuenta.perfil'));

        $avatar = $user->fresh()->avatar;
        $this->assertNotNull($avatar);
        Storage::disk('public')->assertExists($avatar->variants['sm']['path']);

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertSee($avatar->url('md'), false);
    }

    #[Test]
    public function photos_that_are_too_small_or_not_images_are_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProfilePhoto::class)
            ->set('photo', UploadedFile::fake()->image('chica.jpg', 120, 150))
            ->assertHasErrors(['photo' => 'La foto mide 120 × 150 px y tiene que tener al menos 200 px de cada lado. Probá con la original del celular, sin recortar.']);

        Livewire::actingAs($user)->test(ProfilePhoto::class)
            ->set('photo', UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'))
            ->assertHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    #[Test]
    public function variants_are_generated_in_the_background_and_the_old_photo_stays_until_then(): void
    {
        $user = User::factory()->create();
        $images = app(ImageService::class);
        $old = $images->replace($user, 'avatar', UploadedFile::fake()->image('vieja.png', 400, 400))->refresh();

        Queue::fake();
        $new = $images->replace($user, 'avatar', UploadedFile::fake()->image('nueva.png', 400, 400));

        Queue::assertPushed(ProcessMediaVariants::class, fn (ProcessMediaVariants $job) => $job->media->is($new));
        $this->assertSame('processing', $new->status);
        Storage::disk('local')->assertExists($new->original_path);
        $this->assertTrue($user->fresh()->avatar->is($old), 'Mientras tanto se sigue viendo la foto anterior.');

        (new ProcessMediaVariants($new))->handle($images);

        $this->assertTrue($user->fresh()->avatar->is($new));
        $this->assertModelMissing($old);
    }

    #[Test]
    public function a_photo_can_be_rotated_and_removed_from_the_profile(): void
    {
        $user = User::factory()->create();
        $media = app(ImageService::class)->replace($user, 'avatar', UploadedFile::fake()->image('yo.png', 400, 600))->refresh();
        $before = $media->variants['md']['path'];

        Livewire::actingAs($user)->test(ProfilePhoto::class)->call('rotate')->assertRedirect(route('cuenta.perfil'));

        $media->refresh();
        $this->assertSame(90, $media->rotation);
        $this->assertStringEndsWith('md-r90.webp', $media->variants['md']['path']);
        Storage::disk('public')->assertMissing($before);
        Storage::disk('public')->assertExists($media->variants['md']['path']);
        Storage::disk('local')->assertExists($media->original_path);

        Livewire::actingAs($user)->test(ProfilePhoto::class)->call('remove')->assertRedirect(route('cuenta.perfil'));

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('local')->assertMissing($media->original_path);
        Storage::disk('public')->assertMissing($media->variants['md']['path']);
    }

    #[Test]
    public function a_photo_that_cannot_be_processed_is_marked_as_failed(): void
    {
        $user = User::factory()->create();
        Queue::fake();
        $media = app(ImageService::class)->replace($user, 'avatar', UploadedFile::fake()->image('yo.png', 400, 400));
        Storage::disk('local')->delete($media->original_path);

        (new ProcessMediaVariants($media))->failed(new \RuntimeException('No encontramos el original de la foto.'));

        $this->assertSame('failed', $media->fresh()->status);
        $this->assertNull($user->fresh()->avatar);
    }
}

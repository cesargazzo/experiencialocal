<?php

namespace Tests\Feature;

use App\Livewire\ProfilePhoto;
use App\Models\Experience;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $media = app(ImageService::class)->replace($experience, 'cover', UploadedFile::fake()->image('patio.jpg', 3000, 2000));

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

        $first = $images->replace($user, 'avatar', UploadedFile::fake()->image('a.png', 400, 400));
        $second = $images->replace($user, 'avatar', UploadedFile::fake()->image('b.png', 400, 400));

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
            ->set('photo', UploadedFile::fake()->image('chica.jpg', 120, 120))
            ->assertHasErrors('photo');

        Livewire::actingAs($user)->test(ProfilePhoto::class)
            ->set('photo', UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'))
            ->assertHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }
}

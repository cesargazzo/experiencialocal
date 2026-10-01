<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Livewire\ManageExperience;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function publishedExperience(): Experience
    {
        return Experience::factory()->create(['status' => ExperienceStatus::Published, 'published_at' => now()]);
    }

    #[Test]
    public function the_host_adds_several_photos_reorders_and_removes_them_without_leaving_published(): void
    {
        $experience = $this->publishedExperience();
        $host = $experience->host->user;

        Livewire::actingAs($host)->test(ManageExperience::class, ['experience' => $experience])
            ->set('photos', [UploadedFile::fake()->image('a.jpg', 1200, 900), UploadedFile::fake()->image('b.jpg', 1200, 900), UploadedFile::fake()->image('c.jpg', 900, 1200)])
            ->assertHasNoErrors();

        $photos = $experience->galleryPhotos()->get();
        $this->assertCount(3, $photos);
        $this->assertSame([1, 2, 3], $photos->pluck('position')->all());
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);
        Storage::disk('public')->assertExists($photos[0]->variants['thumb']['path']);

        Livewire::actingAs($host)->test(ManageExperience::class, ['experience' => $experience])
            ->call('movePhoto', $photos[2]->id, -1)
            ->call('removePhoto', $photos[0]->id);

        $this->assertSame([$photos[2]->id, $photos[1]->id], $experience->galleryPhotos()->pluck('id')->all());

        $this->get(route('experiencias.show', $experience))->assertOk()
            ->assertSee($photos[2]->url('thumb'), false)->assertSee('id="fotos"', false);
    }

    #[Test]
    public function the_gallery_has_a_limit_and_only_the_owner_can_touch_it(): void
    {
        config(['tinku.images.collections.gallery.max' => 2]);
        $experience = $this->publishedExperience();

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('photos', [UploadedFile::fake()->image('a.jpg', 800, 800), UploadedFile::fake()->image('b.jpg', 800, 800), UploadedFile::fake()->image('c.jpg', 800, 800)])
            ->assertHasErrors('photos');
        $this->assertSame(0, $experience->galleryUploads()->count());

        Livewire::actingAs(User::factory()->create())->test(ManageExperience::class, ['experience' => $experience])->assertForbidden();
    }
}

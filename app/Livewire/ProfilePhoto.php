<?php

namespace App\Livewire;

use App\Services\ImageService;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Foto de perfil de la cuenta. Se sube, se guarda el original y se generan
 * las versiones de avatar.
 */
class ProfilePhoto extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public function updatedPhoto(ImageService $images): void
    {
        [$minWidth, $minHeight] = config('tinku.images.collections.avatar.min');

        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('tinku.images.max_upload_kb'), "dimensions:min_width={$minWidth},min_height={$minHeight}"],
        ], [], ['photo' => 'foto de perfil']);

        $user = auth()->user();
        $images->replace($user, 'avatar', $this->photo, $user->name);

        $this->reset('photo');
        $user->unsetRelation('avatar');
        session()->flash('photo-status', 'Actualizamos tu foto.');
    }

    public function render()
    {
        return view('livewire.profile-photo', ['user' => auth()->user()->load('avatar')]);
    }
}

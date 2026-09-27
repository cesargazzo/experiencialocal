<?php

namespace App\Livewire;

use App\Models\Media;
use App\Rules\ImageSize;
use App\Services\ImageService;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Foto de perfil: se sube con barra de progreso, las versiones se generan
 * en segundo plano y se puede girar o quitar.
 */
class ProfilePhoto extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    /** Mientras hay una foto procesándose, la vista consulta el estado cada dos segundos. */
    public bool $waiting = false;

    public function mount(): void
    {
        $this->waiting = (bool) auth()->user()->latestAvatarUpload?->isProcessing();
    }

    public function updatedPhoto(ImageService $images): void
    {
        $this->validate(['photo' => ['required', ImageSize::forCollection('avatar')]], [], ['photo' => 'foto de perfil']);

        try {
            $images->replace(auth()->user(), 'avatar', $this->photo, auth()->user()->name);
        } catch (InvalidArgumentException $e) {
            $this->addError('photo', $e->getMessage());

            return;
        }

        $this->reset('photo');
        $this->waiting = true;
        $this->checkProcessing();
    }

    public function rotate(ImageService $images): void
    {
        $avatar = auth()->user()->avatar;

        if ($avatar && ! $avatar->isProcessing()) {
            $images->rotate($avatar);
            $this->waiting = true;
            $this->checkProcessing();
        }
    }

    public function remove(): void
    {
        Media::query()
            ->where('mediable_type', auth()->user()->getMorphClass())
            ->where('mediable_id', auth()->id())
            ->where('collection', 'avatar')
            ->get()
            ->each->delete();

        session()->flash('status', 'Quitamos tu foto.');
        $this->redirectRoute('cuenta.perfil');
    }

    /** Cuando termina el procesamiento, recarga para que el encabezado muestre la foto nueva. */
    public function checkProcessing(): void
    {
        $latest = auth()->user()->latestAvatarUpload()->first();

        if ($this->waiting && $latest && ! $latest->isProcessing()) {
            $this->waiting = false;

            if ($latest->status === 'failed') {
                $this->addError('photo', 'No pudimos procesar esa foto. Probá con otra.');

                return;
            }

            session()->flash('status', 'Tu foto está lista.');
            $this->redirectRoute('cuenta.perfil');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $user->unsetRelation('avatar')->unsetRelation('latestAvatarUpload')->load(['avatar', 'latestAvatarUpload']);

        return view('livewire.profile-photo', ['user' => $user]);
    }
}

{{-- Barra de progreso de Livewire. Va dentro del mismo contenedor que el input de archivo. --}}
<div class="upload-progress" x-show="uploading" x-cloak role="progressbar" aria-label="Subiendo la foto" x-bind:aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
  <div class="upload-progress__bar" x-bind:style="'width:' + progress + '%'"></div>
  <span class="hint" x-text="'Subiendo… ' + progress + '%'"></span>
</div>

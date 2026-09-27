<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('mediable');
            $table->string('collection', 30); // avatar, cover
            // Original tal como se subió, en un disco privado: puede traer metadatos como la ubicación GPS.
            $table->string('original_disk', 20);
            $table->string('original_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 60);
            $table->unsignedInteger('size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            // Versiones optimizadas y públicas: {"md": {"path", "width", "height", "size"}, ...}
            $table->string('variants_disk', 20);
            $table->jsonb('variants');
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

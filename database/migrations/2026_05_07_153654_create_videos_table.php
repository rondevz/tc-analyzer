<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('creator_handle');
            $table->string('tiktok_id');
            $table->string('tiktok_url');
            $table->enum('status', ['pending', 'downloaded', 'transcribed', 'classified', 'done', 'failed'])->default('pending');
            $table->text('transcript')->nullable();
            $table->enum('audio_class', ['speech', 'song', 'noise'])->nullable();
            $table->string('frame_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('creator_handle')->references('handle')->on('creators');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};

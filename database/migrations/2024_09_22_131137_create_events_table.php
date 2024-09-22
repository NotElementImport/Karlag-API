<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->text('slug');
            $table->text('title_ru');
            $table->text('title_kk')->nullable();
            $table->text('title_en')->nullable();
            $table->longText('content_ru');
            $table->longText('content_kk')->nullable();
            $table->longText('content_en')->nullable();
            $table->text('tags');
            $table->dateTime('start_at');
            $table->integer('author_id');
            $table->integer('image_id')->nullable();
            $table->boolean('delete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

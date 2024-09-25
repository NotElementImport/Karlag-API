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
        Schema::create('represseds', function (Blueprint $table) {
            $table->id();
            $table->text('slug');
            $table->text('fio');
            $table->integer('birthday_year')->nullable();
            $table->integer('death_year')->nullable();
            $table->longText('content_ru');
            $table->longText('content_kk')->nullable();
            $table->longText('content_en')->nullable();
            $table->integer('author_id');
            $table->boolean('delete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('represseds');
    }
};

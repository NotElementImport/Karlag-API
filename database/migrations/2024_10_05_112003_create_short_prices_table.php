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
        Schema::create('short_prices', function (Blueprint $table) {
            $table->id();
            $table->string('title_ru');
            $table->string('title_kk')->nullable();
            $table->string('title_en')->nullable();
            $table->integer('adult');
            $table->integer('student');
            $table->integer('children');
            $table->integer('pensioner');
            $table->integer('delete');
            $table->integer('index_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_prices');
    }
};

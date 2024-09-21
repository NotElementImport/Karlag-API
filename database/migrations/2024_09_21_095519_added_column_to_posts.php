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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('content_en');
            $table->string('title_en');
        });

        Schema::table('price_groups', function (Blueprint $table) {
            $table->string('title_en');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->string('title_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};

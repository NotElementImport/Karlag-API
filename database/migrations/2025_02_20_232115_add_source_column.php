<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table("represseds", function (Blueprint $table) {
            $table->text("source");
        });
    }

    public function down(): void
    {
        Schema::table("represseds", function (Blueprint $table) {
            $table->dropColumn("source");
        });
    }
};

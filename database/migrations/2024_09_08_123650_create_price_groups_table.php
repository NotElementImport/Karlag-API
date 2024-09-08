<?php

use App\Models\PriceGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title_ru');
            $table->string('title_kk');
            $table->integer('order_index');
            $table->boolean('delete');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->foreignIdFor(PriceGroup::class);
        });

        PriceGroup::factory()->create([
            'title_ru' => '',
            'title_kk' => '',
            'order_index' => 0,
            'delete' => 0
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('price_groups');

        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeignIdFor(PriceGroup::class);
        });
    }
};

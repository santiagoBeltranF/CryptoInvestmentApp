<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
    Schema::create('crypto_histories', function ($table) {
        $table->id();
        $table->foreignId('cryptocurrency_id')->constrained();
        $table->decimal('price', 18, 8);
        $table->decimal('percent_change_24h', 8, 2);
        $table->decimal('market_cap', 20, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_histories');
    }
};

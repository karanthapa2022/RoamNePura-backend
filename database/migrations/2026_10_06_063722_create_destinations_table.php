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
        Schema::create('destinations', function (Blueprint $table) {
    $table->id();

    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description');
    $table->string('image')->nullable();

    $table->string('location')->nullable();
    $table->string('best_time')->nullable();
    $table->string('duration')->nullable();

    $table->text('highlights')->nullable();

    $table->boolean('is_published')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};

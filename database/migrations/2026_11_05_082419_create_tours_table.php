
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description');

            $table->string('duration');
            $table->string('difficulty');

            $table->decimal('price', 10, 2);

            $table->string('category')->nullable();
            $table->string('image')->nullable();

            $table->text('highlights')->nullable();
            $table->json('itinerary')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};

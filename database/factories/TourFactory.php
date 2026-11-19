<?php

namespace Database\Factories;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'name'           => ucwords($name),
            'slug'           => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description'    => $this->faker->paragraph(),
            'duration'       => $this->faker->randomElement(['3 Days', '7 Days', '14 Days']),
            'difficulty'     => $this->faker->randomElement(['Easy', 'Moderate', 'Hard']),
            'price'          => $this->faker->randomFloat(2, 200, 5000),
            'category'       => $this->faker->randomElement(['Trekking', 'Cultural', 'Wildlife']),
            'is_published'   => false,
            'itinerary'      => [],
            'gallery_images' => [],
        ];
    }
}

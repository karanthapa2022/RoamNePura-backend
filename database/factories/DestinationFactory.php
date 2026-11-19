<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->city() . ' ' . $this->faker->randomNumber(3);
        return [
            'name'         => $name,
            'slug'         => Str::slug($name),
            'description'  => $this->faker->paragraph(),
            'location'     => $this->faker->city() . ', Nepal',
            'latitude'     => $this->faker->latitude(26.3, 30.4),
            'longitude'    => $this->faker->longitude(80.0, 88.2),
            'best_time'    => 'March - May',
            'duration'     => $this->faker->randomElement(['3 Days', '5 Days', '7 Days']),
            'highlights'   => null,
            'is_published' => false,
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->name(),
            'email'       => $this->faker->safeEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'tour'        => $this->faker->randomElement([
                'Everest Base Camp',
                'Annapurna Circuit',
                'Chitwan Safari',
            ]),
            'travel_date' => $this->faker->dateTimeBetween('+7 days', '+1 year')->format('Y-m-d'),
            'travelers'   => $this->faker->numberBetween(1, 10),
            'message'     => $this->faker->optional()->sentence(),
            'status'      => 'pending',
        ];
    }
}


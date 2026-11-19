<?php

namespace Database\Factories;

use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'name'           => $this->faker->name(),
            'email'          => $this->faker->safeEmail(),
            'phone'          => $this->faker->optional()->phoneNumber(),
            'destination_id' => null,
            'message'        => $this->faker->paragraph(),
            'status'         => 'new',
        ];
    }
}


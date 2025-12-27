<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Batch;
use App\Models\Commune;

class BatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Batch::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement(["pending","sent","returned"]),
            'expected_delivery_date' => $this->faker->dateTime(),
            'commune_id' => Commune::factory(),
        ];
    }
}

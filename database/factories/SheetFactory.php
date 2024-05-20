<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\;
use App\Models\Commune;
use App\Models\Sheet;

class SheetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sheet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->numberBetween(-10000, 10000),
            'source' => $this->faker->word(),
            'signatureCount' => $this->faker->numberBetween(-10000, 10000),
            'verifiedCount' => $this->faker->numberBetween(-10000, 10000),
            'user_id' => ::factory(),
            'commune_id' => Commune::factory(),
            'batch_id' => ::factory(),
            'status' => $this->faker->randomElement(["recorded","added2batch","processed","faulty"]),
        ];
    }
}

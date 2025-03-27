<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CoreAnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "title"=>fake()->sentence(4),
            "message"=>fake()->paragraph(10),
            "image"=>"https://picsum.photos/100/100",
            "is_active" => 1,
            "should_broadcast"=>fake()->numberBetween(0,1),
            "start_date"=>Carbon::now(),
            "end_date"=>Carbon::now()->addDays(7),
        ];
    }
}

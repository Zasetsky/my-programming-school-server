<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->randomNumber(),
            'subject_code' => $this->faker->unique()->word(),
            'user_id' => $this->faker->randomNumber(),
            // Сделайте так, чтобы это был действительный ID пользователя
            'name' => $this->faker->word(),
            'modules' => json_encode([
                // Пример JSON-структуры для modules
                [
                    'nextLessonDate' => $this->faker->date('d-m-Y'),
                    'startTime' => $this->faker->time(),
                    'duration' => $this->faker->randomElement(['1 hour', '1 hour 30 minutes', '2 hours']),
                    'completedLessonCount' => $this->faker->randomDigit,
                ]
            ]),
        ];
    }
}
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employer>
 */
class EmployerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
             'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'slogan' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'email' => $this->faker->unique()->companyEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'profile_image' => $this->faker->imageUrl(200, 200, 'business'),
            'company_type' => $this->faker->randomElement(['Startup', 'Enterprise', 'SME', 'Freelancer']),
            'technologies_using' => json_encode($this->faker->words(5)),
            'isVerified' => $this->faker->randomElement([null, 'yes', 'no']),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'status' => $this->faker->boolean(),
            'facebook' => 'https://facebook.com/' . $this->faker->userName(),
            'twitter' => 'https://twitter.com/' . $this->faker->userName(),
            'youtube' => 'https://youtube.com/' . $this->faker->userName(),
            'github' => 'https://github.com/' . $this->faker->userName(),
            'views' => $this->faker->numberBetween(0, 10000),
            'isFeatued' => $this->faker->boolean(),
            'last_login' => $this->faker->dateTime(),
            'active' => $this->faker->boolean(),
        ];
    }
}

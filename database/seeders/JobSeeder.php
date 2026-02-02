<?php

namespace Database\Seeders;

use App\Models\Job;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Job::create([

            'title' => 'Php develper',
            'category_id' => '1',
            'description' => 'This is a test description',
            'skills' => 'php, laravel',
            'salary' => '50000',
            'deadline' => '2024-03-20',
            'open_position' => 'software developer',
            'location' => 'Dhaka, Bangladesh',
            'type' => 'full_time',
            // 'employer_id' => '1',
            'experience' => '4 years',
            'status' => true

        ]);
        Job::create([

            'title' => 'Web develper',
            'category_id' => '1',
            'description' => 'This is a test description',
            'skills' => 'javascript, laravel',
            'salary' => '40000',
            'deadline' => '2024-03-23',
            'open_position' => 'software developer',
            'location' => 'UK',
            'type' => 'remote',
            // 'employer_id' => '1',
            'experience' => '5 years',
            'status' => true
            
        ]);
        Job::create([

            'title' => 'Python develper',
            'category_id' => '1',
            'description' => 'This is a test description',
            'skills' => 'Python, django',
            'salary' => '80000',
            'deadline' => '2024-03-25',
            'open_position' => 'software developer',
            'location' => 'Dhaka, Bangladesh',
            'type' => 'freelance',
            // 'employer_id' => '1',
            'experience' => '3 years',
            'status' => true

        ]);
    }
}

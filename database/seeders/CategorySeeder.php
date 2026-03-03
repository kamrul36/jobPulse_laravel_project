<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{

		$superAdmin = User::whereHas('role', function ($query) {
			$query->where('slug', 'super_admin');
		})->first();


		$categories = [
			'Technology & IT',
			'Software Development',
			'Web Development',
			'Mobile Development',
			'Data Science & Analytics',
			'DevOps & Cloud',
			'Cybersecurity',
			'UI/UX Design',
			'Graphic Design',
			'Digital Marketing',
			'Content Writing',
			'SEO & SEM',
			'Sales & Business Development',
			'Customer Support',
			'Human Resources',
			'Finance & Accounting',
			'Legal & Compliance',
			'Project Management',
			'Product Management',
			'Operations & Logistics',
			'Healthcare & Medical',
			'Education & Training',
			'Engineering',
			'Architecture & Construction',
			'Hospitality & Tourism',
			'Food & Beverage',
			'Real Estate',
			'Retail & E-commerce',
			'Manufacturing',
			'Media & Entertainment',
			'Photography & Videography',
			'Administrative & Office',
			'Virtual Assistant',
			'Translation & Languages',
			'Research & Development',
			'Quality Assurance',
			'Consulting',
			'Freelance & Gig Work',
			'Internships & Entry Level',
			'Other',
		];

		foreach ($categories as $name) {
			DB::table('categories')->insert([
				'name' => $name,
				'slug' => Str::slug($name),
				'status' => 1,
				'created_by' => $superAdmin->id,
				'created_at' => now(),
				'updated_at' => now(),
			]);
		}
		$this->command->info('✅ ' . count($categories) . ' categories seeded!');
	}
}

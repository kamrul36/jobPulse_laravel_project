<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([

        	'name' => 'Design & Creative',
        	'icon' => 'flaticon-tour',
        	'slug' => 'design-creative',
        	'parent_id' => null,
        	'status' => 1,

        ]);
         Category::create([

        	'name' => 'Design & Development',
        	'icon' => 'flaticon-cms',
        	'slug' => 'design-development',
        	'parent_id' => null,
        	'status' => 1

        ]);
        
           Category::create([

        	'name' => 'Sales & Marketing',
        	'icon' => 'flaticon-report',
        	'slug' => 'sales-marketing',
        	'parent_id' => null,
        	'status' => 1,

        ]);
            Category::create([

        	'name' => 'Mobile Application',
        	'icon' => 'flaticon-app',
        	'slug' => 'mobile-application',
        	'parent_id' => null,
        	'status' => 1,

        ]);
             Category::create([

        	'name' => 'Construction',
        	'icon' => 'flaticon-helmet',
        	'slug' => 'construction',
        	'parent_id' => null,
        	'status' => 1,

        ]);
              Category::create([

        	'name' => 'Information Technology',
        	'icon' => 'flaticon-high-tech',
        	'slug' => 'information-technology',
        	'parent_id' => null,
        	'status' => 1,

        ]);
           Category::create([

        	'name' => 'Real Estate',
        	'icon' => 'flaticon-real-estate',
        	'slug' => 'real-estate',
        	'parent_id' => null,
        	'status' => 1,

        ]);

        Category::create([

        	'name' => 'Content Writer',
        	'icon' => 'flaticon-content',
        	'slug' => 'content-writer',
        	'parent_id' => null,
        	'status' => 1,

        ]);
    }
}

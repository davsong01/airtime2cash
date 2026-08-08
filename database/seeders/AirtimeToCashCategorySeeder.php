<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class AirtimeToCashCategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::updateOrCreate(
            [
                'slug' => 'airtime2cash',
                'type' => 'airtime2cash',
            ],
            [
                'name' => 'Airtime to Cash',
                'icon' => '',
                'display_name' => 'Airtime to Cash',
                'status' => 'active',
                'order' => 1000,
                'unique_element' => 'phone',
                'discount_type' => 'percentage',
            ]
        );
    }
}

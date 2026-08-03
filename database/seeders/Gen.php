<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class Gen extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Insert multiple rows into the users table with fake data
        $firstTableData = [];
        for ($i = 0; $i < 10; $i++) {
            $firstTableData[] = [
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'phone' => $faker->phoneNumber,
                'email_verified_at' => now(),
                'referral' => 'softee',
                'created_at' => now(),
                'updated_at' => now(),
                'username' => $faker->userName
            ];
        }

        // Insert data into the users table and get the inserted IDs
        $firstTableIds = User::insert($firstTableData);
        $firstTableData = [2,3,4,5,6,7,8,9,10,11,12];

        // Use the inserted IDs to insert into the second table with fake data
        $secondTableData = [];
        foreach ($firstTableData as $firstTableId) {
            $secondTableData[] = [
                'customer_id' => $firstTableId,
            ];
        }

        // Insert data into the customers table
        DB::table('customers')->insert($secondTableData);
    }
}

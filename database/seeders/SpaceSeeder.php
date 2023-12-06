<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample data for the space collection
        $spaces = [
            [
                'space_name' => 'squidgame',
                'space_description' => 'squidgame game provider',
                'space_owner_user' => [
                    'space_owner_user_email' => 'Jeff@example.com',
                ],
                'space_shared_user' => [
                    [
                        'space_shared_user_email' => 'Howard@example.com',
                    ],
                    [
                        'space_shared_user_email' => 'Fremont@example.com',
                    ],
                ],
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null, // You can adjust this based on your actual field definition
            ],
            // Add more space entries as needed
        ];

        // Insert data into the space collection
        DB::table('spaces')->insert($spaces);    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample data for the board table
        $components = [
            [
                'board_id' => '65570aaae0d2937b08fb7d84',
                'component_name' => 'landing_index',
                'component_description' => 'landing page',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null
            ],
            [
                'board_id' => '65570aaae0d2937b08fb7d84',
                'component_name' => 'game_lobby',
                'component_description' => 'game lobby page',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null
            ],
            [
                'board_id' => '65570aaae0d2937b08fb7d84',
                'component_name' => 'media_gallery',
                'component_description' => 'media gallery',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null
            ],
            // Add more board entries as needed
        ];

        // Insert data into the board table
        DB::table('components')->insert($components);    
    }
}

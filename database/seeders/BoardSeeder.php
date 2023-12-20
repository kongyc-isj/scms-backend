<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample data for the board table
        $boards = [
            [
                'board_name' => 'go888king',
                'board_description' => 'go888king operator',
                'board_default_language_code' => 'en',
                'board_api_key' => 'efdjnewonfeowfnwe',
                'board_owner_user' => [
                    'board_owner_email' => 'Jeff@example.com',
                    'board_owner_api_key' => 'efdjnewonfefeowfnwer',
                ],
                'board_shared_user' => [
                    [
                        'board_shared_user_email' => 'Howard@example.com',
                        'board_shared_user_create_access' => 1,
                        'board_shared_user_read_access' => 1,
                        'board_shared_user_update_access' => 1,
                        'board_shared_user_delete_access' => 1,
                        'board_shared_user_api_key' => 'efdjnewonfeowfnwe',
                    ],
                    [
                        'board_shared_user_email' => 'Fremont@example.com',
                        'board_shared_user_create_access' => 1,
                        'board_shared_user_read_access' => 1,
                        'board_shared_user_update_access' => 1,
                        'board_shared_user_delete_access' => 0,
                        'board_shared_user_api_key' => 'fbnwefbnweojfnfe',
                    ],
                ],
                'space_id' => '6557029be0d2937b08fb7d83',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => null, // You can adjust this based on your actual field definition
                'deleted_at' => null, // You can adjust this based on your actual field definition
            ],
            // Add more board entries as needed
        ];

        // Insert data into the board table
        DB::table('boards')->insert($boards);
    }
}

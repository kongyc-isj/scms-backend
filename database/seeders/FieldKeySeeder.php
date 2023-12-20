<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $field_key = [
            [
                'component_id' => '6557029be0d2937b08fb7d83',
                'field_type_name' => '6557029be0d2937b08fb7d82',
                'field_key_name' => 'landing_index_username',
                'field_key_description' => 'go888king landing page username field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'component_id' => '6557029be0d2937b08fb7d83',
                'field_type_name' => '6557029be0d2937b08fb7d82',
                'field_key_name' => 'landing_index_password',
                'field_key_description' => 'go888king landing page password field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'component_id' => '6557029be0d2937b08fb7d83',
                'field_type_name' => '6557029be0d2937b08fb7d82',
                'field_key_name' => 'landing_index_login',
                'field_key_description' => 'go888king landing page login button',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'component_id' => '6557029be0d2937b08fb7d83',
                'field_type_name' => '6557029be0d2937b08fb7d82',
                'field_key_name' => 'landing_index_demo',
                'field_key_description' => 'go888king landing page demo button',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'component_id' => '6557029be0d2937b08fb7d83',
                'field_type_name' => '6557029be0d2937b08fb7d82',
                'field_key_name' => 'landing_index_error_msg',
                'field_key_description' => 'go888king landing page login error message',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ]
            // Add more board entries as needed
        ];

        // Insert data into the board table
        DB::table('field_key')->insert($field_key);    
    }
}

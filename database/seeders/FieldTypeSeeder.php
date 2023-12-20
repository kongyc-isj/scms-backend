<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $field_type = [
            [
                'field_type_tag' => 'text',
                'field_type_name' => 'short text',
                'field_type_description' => 'Small or long text like title or description',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'email',
                'field_type_description' => 'Email field with validations format',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'rich text',
                'field_type_description' => 'A rich text editor with formatting options',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'media',
                'field_type_description' => 'Best for avatar, profile picture or cover',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            // Add more board entries as needed
        ];

        $field_type = [
            [
                'field_type_tag' => 'text',
                'field_type_name' => 'short text',
                'field_type_description' => 'Small or long text like title or description',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_tag' => 'text',
                'field_type_name' => 'long text',
                'field_type_description' => 'Small or long text like title or description',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'email',
                'field_type_description' => 'Email field with validations format',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'rich text',
                'field_type_description' => 'A rich text editor with formatting options',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            [
                'field_type_name' => 'media',
                'field_type_description' => 'Best for avatar, profile picture or cover',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,            
            ],
            // Add more board entries as needed
        ];

        $field_type_api_return_format = [
            "text" => [
                [
                    "short text",
                    "long text"
                ],
                [
                    "field_type_name"        => "long text",
                    "field_type_description" => "Small or long text like title or description",
                ],
            ],
            "email" => [
                [
                    "field_type_name"        => "email",
                    "field_type_description" => "Small or long text like title or description",
                ],
            ]
        ];

        // Insert data into the board table
        DB::table('field_type')->insert($field_type);    
    }
}

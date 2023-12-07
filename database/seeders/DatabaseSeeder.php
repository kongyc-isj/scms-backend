<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            BoardSeeder::class,
            ComponentSeeder::class,
            FieldDataSeeder::class,
            FieldKeySeeder::class,
            FieldTypeSeeder::class,
            LanguageSeeder::class,
            MediaGallerySeeder::class,
            SpaceSeeder::class,
            // other seeders...
        ]);   
    }
}

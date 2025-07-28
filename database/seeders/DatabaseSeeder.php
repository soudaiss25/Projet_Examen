<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // appel aux seeders
        $this->call([
            UserSeeder::class,
            ClasseSeeder::class,
            MatiereSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classe;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des classes à créer
        $classes = [
            '6e',
            '5e',
            '4e',
            '3e',
            '2nde',
            '1ère',
            'Terminale'
        ];

        foreach ($classes as $nom) {
            Classe::create(['nom' => $nom]);
        }
    }
}

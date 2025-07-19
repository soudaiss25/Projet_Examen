<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Matiere;

class MatiereSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des matières avec coefficients par défaut
        $matieres = [
            ['nom' => 'Mathématiques',       'coefficient' => 4],
            ['nom' => 'Français',            'coefficient' => 3],
            ['nom' => 'Physique-Chimie',     'coefficient' => 4],
            ['nom' => 'Histoire-Géographie', 'coefficient' => 2],
            ['nom' => 'Anglais',             'coefficient' => 2],
        ];

        foreach ($matieres as $matiere) {
            Matiere::create($matiere);
        }
    }
}

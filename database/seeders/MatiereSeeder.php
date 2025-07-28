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
            ['nom' => 'Mathématiques',       'coefficient' => 4, 'niveau' => '1ère'],
            ['nom' => 'Français',            'coefficient' => 3, 'niveau' => '1ère'],
            ['nom' => 'Physique-Chimie',     'coefficient' => 4, 'niveau' => '1ère'],
            ['nom' => 'Histoire-Géographie', 'coefficient' => 2, 'niveau' => '1ère'],
            ['nom' => 'Anglais',             'coefficient' => 2, 'niveau' => '1ère'],
        ];


        foreach ($matieres as $matiere) {
            Matiere::create($matiere);
        }
    }
}

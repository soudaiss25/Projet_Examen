<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classe;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $anneeScolaire = now()->year . '-' . (now()->year + 1);

        $classes = [
            [
                'nom' => '6e',
                'niveau' => 'Collège',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => '5e',
                'niveau' => 'Collège',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => '4e',
                'niveau' => 'Collège',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => '3e',
                'niveau' => 'Collège',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => '2nde',
                'niveau' => 'Lycée',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => '1ère',
                'niveau' => 'Lycée',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
            [
                'nom' => 'Terminale',
                'niveau' => 'Lycée',
                'capacite' => 30,
                'annee_scolaire' => $anneeScolaire,
            ],
        ];

        foreach ($classes as $classe) {
            Classe::create($classe);
        }
    }
}

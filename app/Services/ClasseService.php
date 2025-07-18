<?php
namespace App\Services;

use App\Models\Classe;
use App\Models\Matiere;

class ClasseService
{
    /**
     * Affecter des matières à une classe avec coefficients
     */
    public function affecterMatieres(Classe $classe, array $matieresAvecCoefficients)
    {
        // Format: [['matiere_id' => 1, 'coefficient' => 3], ...]
        $pivotData = [];

        foreach ($matieresAvecCoefficients as $data) {
            $pivotData[$data['matiere_id']] = ['coefficient' => $data['coefficient']];
        }

        $classe->matieres()->sync($pivotData);

        return $classe->load('matieres');
    }

    /**
     * Affectation automatique des matières selon le niveau
     */
    public function affectationAutomatiqueMatieres(Classe $classe)
    {
        $niveau = $classe->niveau;
        $isCollege = in_array($niveau, ['6ème', '5ème', '4ème', '3ème']);
        $isLycee = in_array($niveau, ['2nde', '1ère', 'Terminale']);

        $matieres = collect();

        if ($isCollege) {
            $matieres = Matiere::whereIn('niveau', ['college', 'tous'])->get();
        } elseif ($isLycee) {
            $matieres = Matiere::whereIn('niveau', ['lycee', 'tous'])->get();
        }

        $matieresAvecCoefficients = [];

        foreach ($matieres as $matiere) {
            $coefficient = $this->getCoefficient($matiere->nom, $niveau);
            $matieresAvecCoefficients[] = [
                'matiere_id' => $matiere->id,
                'coefficient' => $coefficient
            ];
        }

        return $this->affecterMatieres($classe, $matieresAvecCoefficients);
    }

    /**
     * Déterminer le coefficient selon la matière et le niveau
     */
    private function getCoefficient($nomMatiere, $niveau)
    {
        $coefficients = [
            'Mathématiques' => 4,
            'Français' => 4,
            'Anglais' => 3,
            'Histoire-Géographie' => 3,
            'Sciences Physiques' => 3,
            'SVT' => 2,
            'EPS' => 1,
            'Philosophie' => 4,
            'Chimie' => 3,
            'Physique' => 3,
            'Économie' => 3,
        ];

        return $coefficients[$nomMatiere] ?? 2;
    }
}

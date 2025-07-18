<?php
namespace App\Services;

use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\Classe;

class EnseignantService
{
    /**
     * Affecter des matières à un enseignant
     */
    public function affecterMatieres(Enseignant $enseignant, array $matiereIds)
    {
        // Vérifier que les matières existent
        $matieres = Matiere::whereIn('id', $matiereIds)->get();

        if ($matieres->count() !== count($matiereIds)) {
            throw new \Exception('Certaines matières n\'existent pas');
        }

        // Affecter les matières
        $enseignant->matieres()->sync($matiereIds);

        return $enseignant->load('matieres');
    }

    /**
     * Affecter des classes à un enseignant
     */
    public function affecterClasses(Enseignant $enseignant, array $classeIds)
    {
        // Vérifier que les classes existent
        $classes = Classe::whereIn('id', $classeIds)->get();

        if ($classes->count() !== count($classeIds)) {
            throw new \Exception('Certaines classes n\'existent pas');
        }

        // Affecter les classes
        $enseignant->classes()->sync($classeIds);

        return $enseignant->load('classes');
    }

    /**
     * Affectation automatique basée sur la spécialité
     */
    public function affectationAutomatique(Enseignant $enseignant)
    {
        // Logique d'affectation basée sur la spécialité
        $specialite = strtolower($enseignant->specialite);

        $matiereIds = [];

        switch ($specialite) {
            case 'mathematiques':
                $matiereIds = Matiere::where('nom', 'LIKE', '%mathématiques%')->pluck('id');
                break;
            case 'français':
                $matiereIds = Matiere::where('nom', 'LIKE', '%français%')->pluck('id');
                break;
            case 'sciences':
                $matiereIds = Matiere::whereIn('nom', ['Sciences Physiques', 'Chimie', 'Physique', 'SVT'])
                    ->pluck('id');
                break;
            case 'langues':
                $matiereIds = Matiere::where('nom', 'LIKE', '%anglais%')->pluck('id');
                break;
        }

        if ($matiereIds->isNotEmpty()) {
            $this->affecterMatieres($enseignant, $matiereIds->toArray());
        }

        return $enseignant;
    }
}

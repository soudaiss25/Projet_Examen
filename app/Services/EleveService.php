<?php
namespace App\Services;

use App\Models\User;
use App\Models\Eleve;
use App\Models\ParentUser;
use App\Models\Classe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EleveService
{
    /**
     * Inscrire un élève avec affectation automatique du parent
     */
    public function inscrireEleve(array $eleveData, array $parentData = null)
    {
        return DB::transaction(function () use ($eleveData, $parentData) {

            // 1. Chercher ou créer le parent
            $parent = $this->getOrCreateParent($parentData);

            // 2. Créer l'utilisateur élève
            $userEleve = User::create([
                'nom' => $eleveData['nom'],
                'prenom' => $eleveData['prenom'],
                'adresse' => $eleveData['adresse'],
                'telephone' => $eleveData['telephone'],
                'email' => $eleveData['email'],
                'password' => Hash::make($eleveData['password'] ?? 'password123'),
                'role' => 'eleve',
                'est_actif' => true,
            ]);

            // 3. Générer matricule
            $matricule = $this->genererMatricule($eleveData['classe_id']);

            // 4. Créer l'élève
            $eleve = Eleve::create([
                'user_id' => $userEleve->id,
                'date_naissance' => $eleveData['date_naissance'],
                'lieu_naissance' => $eleveData['lieu_naissance'],
                'sexe' => $eleveData['sexe'],
                'numero_matricule' => $matricule,
                'classe_id' => $eleveData['classe_id'],
                'parent_id' => $parent->id,
            ]);

            // 5. Mettre à jour le nombre d'enfants du parent
            $parent->increment('nombre_enfants');

            return $eleve;
        });
    }

    /**
     * Chercher ou créer un parent
     */
    private function getOrCreateParent(array $parentData = null)
    {
        if (!$parentData) {
            throw new \Exception('Données parent obligatoires');
        }

        // Chercher par email
        $userParent = User::where('email', $parentData['email'])->first();

        if ($userParent) {
            return $userParent->parentUser;
        }

        // Créer nouveau parent
        $userParent = User::create([
            'nom' => $parentData['nom'],
            'prenom' => $parentData['prenom'],
            'adresse' => $parentData['adresse'],
            'telephone' => $parentData['telephone'],
            'email' => $parentData['email'],
            'password' => Hash::make($parentData['password'] ?? 'password123'),
            'role' => 'parent',
            'est_actif' => true,
        ]);

        return ParentUser::create([
            'user_id' => $userParent->id,
            'profession' => $parentData['profession'] ?? null,
            'nombre_enfants' => 0,
        ]);
    }

    /**
     * Générer matricule unique
     */
    private function genererMatricule($classeId)
    {
        $classe = Classe::find($classeId);
        $annee = date('Y');
        $niveau = substr($classe->niveau, 0, 1);
        $section = $classe->nom;

        // Format: 2024-6A-001
        $prefix = "{$annee}-{$niveau}{$section}";
        $dernierNumero = Eleve::where('numero_matricule', 'LIKE', "{$prefix}%")
                ->count() + 1;

        return $prefix . '-' . str_pad($dernierNumero, 3, '0', STR_PAD_LEFT);
    }
}

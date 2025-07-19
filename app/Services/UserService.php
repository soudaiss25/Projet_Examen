<?php

namespace App\Services;

use App\Models\User;
use App\Models\ParentUser;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Classe;
use App\Models\Matiere;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCredentialsMail;

class UserService
{
    /**
     * Crée un élève et son parent (si besoin), puis envoie les identifiants
     */
    public static function createEleve(array $data): User
    {
        return DB::transaction(function () use ($data) {
                    // Créer ou récupérer le parent
        $userParent = User::firstOrCreate(
            ['email' => $data['parent_email']],
            [
                'nom' => $data['parent_nom'],
                'prenom' => $data['parent_prenom'],
                'telephone' => $data['parent_telephone'],
                'adresse' => $data['parent_adresse'],
                'password' => Hash::make($data['parent_password'] ?? 'password123'),
                'role' => User::ROLE_PARENT,
                'est_actif' => true,
            ]
        );

        $parent = ParentUser::firstOrCreate(
            ['user_id' => $userParent->id],
            [
                'profession' => $data['parent_profession'] ?? null,
                'nombre_enfants' => 0,
            ]
        );

            // Créer l'utilisateur élève
            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'adresse' => $data['adresse'],
                'telephone' => $data['telephone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_ELEVE,
                'est_actif' => true,
            ]);

            // Créer l'entrée dans la table élèves
            Eleve::create([
                'user_id' => $user->id,
                'parent_id' => $parent->id,
                'classe_id' => $data['classe_id'],
            ]);

            // Envoyer l'e-mail au parent
            Mail::to($parent->email)->send(new SendCredentialsMail($user, $data['password']));

            return $user;
        });
    }

    /**
     * Crée un enseignant, lui attribue une classe et une matière, puis envoie ses identifiants
     */
    public static function createEnseignant(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'adresse' => $data['adresse'],
                'telephone' => $data['telephone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_ENSEIGNANT,
                'est_actif' => true,
            ]);

            $enseignant = Enseignant::create([
                'user_id' => $user->id,
                'specialite' => $data['specialite'] ?? 'Général',
                'date_embauche' => $data['date_embauche'] ?? now(),
                'numero_identifiant' => $data['numero_identifiant'] ?? 'ENS' . time(),
            ]);

            // Attribuer classes et matières (si passées en tableau)
            if (!empty($data['classe_ids'])) {
                $enseignant->classes()->sync($data['classe_ids']);
            }

            if (!empty($data['matiere_ids'])) {
                $enseignant->matieres()->sync($data['matiere_ids']);
            }

            // Envoyer les informations de connexion
            Mail::to($user->email)->send(new SendCredentialsMail($user, $data['password']));

            return $user;
        });
    }

    /**
     * Crée un utilisateur parent
     */
    public static function createParent(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'adresse' => $data['adresse'],
                'telephone' => $data['telephone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_PARENT,
                'est_actif' => true,
            ]);

            ParentUser::create([
                'user_id' => $user->id,
                'profession' => $data['profession'] ?? null,
                'nombre_enfants' => 0,
            ]);

            Mail::to($user->email)->send(new SendCredentialsMail($user, $data['password']));

            return $user;
        });
    }
}

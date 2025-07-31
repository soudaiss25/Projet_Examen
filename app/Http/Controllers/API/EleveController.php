<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Models\ParentUser;
use App\Services\EleveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class EleveController extends Controller
{
    protected $eleveService;

    public function __construct(EleveService $eleveService)
    {
        $this->eleveService = $eleveService;
    }

    /**
     * Liste des élèves
     */
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            Log::info('Utilisateur authentifié pour liste élèves:', ['user_id' => $user->id]);

            // Vérifier les permissions
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $eleves = Eleve::with(['user', 'classe', 'parent.user'])->get();

            Log::info('Élèves récupérés:', ['count' => $eleves->count()]);

            return response()->json([
                'status' => 'success',
                'data' => $eleves
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération élèves:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des élèves',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève avec génération automatique du matricule
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== DÉBUT CRÉATION ÉLÈVE ===');
            Log::info('Données reçues:', $request->all());

            $user = JWTAuth::parseToken()->authenticate();
            Log::info('Utilisateur authentifié:', ['user_id' => $user->id, 'role' => $user->role]);

            if (!$user->isAdmin()) {
                Log::warning('Tentative création élève par non-admin:', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut créer un élève'
                ], 403);
            }

            // Validation des données
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'telephone' => 'required|string|max:20|unique:users,telephone',
                'email' => 'required|string|email|max:255|unique:users,email',
                'date_naissance' => 'required|date',
                'lieu_naissance' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'classe_id' => 'required|exists:classes,id',

                // Données parent
                'parent_nom' => 'required|string|max:255',
                'parent_prenom' => 'required|string|max:255',
                'parent_adresse' => 'required|string|max:255',
                'parent_telephone' => 'required|string|max:20',
                'parent_email' => 'required|string|email|max:255',
                'parent_profession' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                Log::error('Erreur validation:', $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Validation réussie, création de l\'élève...');

            // Démarrer une transaction
            DB::beginTransaction();

            try {
                // 1. Créer l'utilisateur élève
                $eleveUser = User::create([
                    'nom' => $request->nom,
                    'prenom' => $request->prenom,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                    'adresse' => $request->adresse,
                    'role' => 'eleve',
                    'password' => bcrypt('password123'), // Mot de passe par défaut
                ]);

                Log::info('Utilisateur élève créé:', ['user_id' => $eleveUser->id]);

                // 2. Vérifier ou créer le parent
                $parentUser = User::where('email', $request->parent_email)->first();

                if (!$parentUser) {
                    // Créer le parent s'il n'existe pas
                    $parentUser = User::create([
                        'nom' => $request->parent_nom,
                        'prenom' => $request->parent_prenom,
                        'email' => $request->parent_email,
                        'telephone' => $request->parent_telephone,
                        'adresse' => $request->parent_adresse,
                        'role' => 'parent',
                        'password' => bcrypt('password123'), // Mot de passe par défaut
                    ]);

                    Log::info('Utilisateur parent créé:', ['user_id' => $parentUser->id]);
                } else {
                    Log::info('Parent existant trouvé:', ['user_id' => $parentUser->id]);
                }

                // 3. Créer ou récupérer l'enregistrement parent
                $parent = ParentUser::firstOrCreate(
                    ['user_id' => $parentUser->id],
                    ['profession' => $request->parent_profession ?? '']
                );

                Log::info('Parent élève créé/trouvé:', ['parent_id' => $parent->id]);

                // 4. Générer le numéro de matricule
                $numeroMatricule = $this->generateMatricule();
                Log::info('Matricule généré:', ['matricule' => $numeroMatricule]);

                // 5. Créer l'élève
                $eleve = Eleve::create([
                    'user_id' => $eleveUser->id,
                    'date_naissance' => $request->date_naissance,
                    'lieu_naissance' => $request->lieu_naissance,
                    'sexe' => $request->sexe,
                    'numero_matricule' => $numeroMatricule,
                    'classe_id' => $request->classe_id,
                    'parent_id' => $parent->id,
                ]);

                Log::info('Élève créé avec succès:', ['eleve_id' => $eleve->id]);

                // Confirmer la transaction
                DB::commit();

                // Charger les relations
                $eleve = $eleve->load(['user', 'classe', 'parent.user']);
                Log::info('=== ÉLÈVE CRÉÉ AVEC SUCCÈS ===', ['eleve' => $eleve->toArray()]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Élève créé avec succès',
                    'data' => $eleve
                ], 201);

            } catch (\Exception $e) {
                // Annuler la transaction en cas d'erreur
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('=== ERREUR CRÉATION ÉLÈVE ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Générer un numéro de matricule unique
     */
    private function generateMatricule(): string
    {
        $year = date('Y');
        $lastEleve = Eleve::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEleve && $lastEleve->numero_matricule) {
            // Extraire le numéro séquentiel du dernier matricule
            $lastNumber = (int) substr($lastEleve->numero_matricule, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: EL2024001, EL2024002, etc.
        return 'EL' . $year . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Afficher un élève spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $eleve = Eleve::with(['user', 'classe', 'parent.user', 'notes.matiere'])->find($id);

            if (!$eleve) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Élève non trouvé'
                ], 404);
            }

            // Vérifier les permissions
            if (!$user->isAdmin() && !$user->isEnseignant() &&
                ($user->isParent() && $user->parentUser->id !== $eleve->parent_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $eleve
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération élève:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, string $id)
    {
        try {
            Log::info('=== DÉBUT MISE À JOUR ÉLÈVE ===', ['id' => $id, 'data' => $request->all()]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut modifier un élève'
                ], 403);
            }

            $eleve = Eleve::find($id);
            if (!$eleve) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Élève non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'adresse' => 'sometimes|string|max:255',
                'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $eleve->user_id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $eleve->user_id,
                'date_naissance' => 'sometimes|date',
                'lieu_naissance' => 'sometimes|string|max:255',
                'sexe' => 'sometimes|in:M,F',
                'classe_id' => 'sometimes|exists:classes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Mettre à jour l'utilisateur
            $eleve->user->update($request->only(['nom', 'prenom', 'adresse', 'telephone', 'email']));

            // Mettre à jour l'élève
            $eleve->update($request->only(['date_naissance', 'lieu_naissance', 'sexe', 'classe_id']));

            Log::info('Élève mis à jour avec succès');

            return response()->json([
                'status' => 'success',
                'message' => 'Élève mis à jour avec succès',
                'data' => $eleve->load(['user', 'classe', 'parent.user'])
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour élève:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer un élève
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer un élève'
                ], 403);
            }

            $eleve = Eleve::find($id);
            if (!$eleve) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Élève non trouvé'
                ], 404);
            }

            // Vérifier s'il y a des notes ou autres dépendances
            if ($eleve->notes()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de supprimer un élève ayant des notes'
                ], 400);
            }

            // Supprimer l'élève et son utilisateur
            $eleve->user->delete();
            $eleve->delete();

            Log::info('Élève supprimé avec succès:', ['id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Élève supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur suppression élève:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
    private function envoyerIdentifiants($user, string $password) {
        Mail::send('emails.credentials', [
            'user' => $user,
            'password' => $password
        ], function($message) use ($user) {
            $message->to($user->email, $user->prenom . ' ' . $user->nom)
                ->subject('Vos identifiants de connexion');
        });
    }

}

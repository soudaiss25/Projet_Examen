<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Services\ClasseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class ClasseController extends Controller
{
    protected $classeService;

    public function __construct(ClasseService $classeService)
    {
        $this->classeService = $classeService;
    }

    /**
     * Liste des classes
     */
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            Log::info('Utilisateur authentifié pour liste classes:', ['user_id' => $user->id]);

            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $classes = Classe::with(['matieres', 'eleves.user'])->get();
            Log::info('Classes récupérées:', ['count' => $classes->count()]);

            return response()->json([
                'status' => 'success',
                'data' => $classes
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération classes:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des classes',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer une nouvelle classe
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== DÉBUT CRÉATION CLASSE ===');
            Log::info('Données reçues:', $request->all());

            $user = JWTAuth::parseToken()->authenticate();
            Log::info('Utilisateur authentifié:', ['user_id' => $user->id, 'role' => $user->role]);

            if (!$user->isAdmin()) {
                Log::warning('Tentative création classe par non-admin:', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut créer une classe'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'niveau' => 'required|string|max:50',
                'capacite' => 'required|integer|min:1|max:50',
                'description' => 'nullable|string|max:500',
                'annee_scolaire' => 'nullable|string|max:10',
                'affectation_automatique' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                Log::error('Erreur validation:', $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Validation réussie, création de la classe...');

            // Préparer les données pour la création
            $classeData = [
                'nom' => $request->nom,
                'niveau' => $request->niveau,
                'capacite' => $request->capacite,
                'description' => $request->description,
                'annee_scolaire' => $request->annee_scolaire ?? now()->year . '-' . (now()->year + 1)
            ];

            Log::info('Données préparées:', $classeData);

            $classe = Classe::create($classeData);
            Log::info('Classe créée avec succès:', ['classe_id' => $classe->id]);

            // Affectation automatique des matières si demandée
            if ($request->affectation_automatique) {
                Log::info('Affectation automatique demandée');
                try {
                    $this->classeService->affectationAutomatiqueMatieres($classe);
                    Log::info('Affectation automatique réussie');
                } catch (\Exception $e) {
                    Log::error('Erreur affectation automatique:', ['error' => $e->getMessage()]);
                    // Continue même si l'affectation échoue
                }
            }

            $classe = $classe->load(['matieres', 'eleves.user']);
            Log::info('=== CLASSE CRÉÉE AVEC SUCCÈS ===', ['classe' => $classe->toArray()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Classe créée avec succès',
                'data' => $classe
            ], 201);

        } catch (\Exception $e) {
            Log::error('=== ERREUR CRÉATION CLASSE ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher une classe spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $classe = Classe::with(['matieres', 'eleves.user', 'enseignants.user'])->find($id);

            if (!$classe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Classe non trouvée'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $classe
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération classe:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour une classe
     */
    public function update(Request $request, string $id)
    {
        try {
            Log::info('=== DÉBUT MISE À JOUR CLASSE ===', ['id' => $id, 'data' => $request->all()]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut modifier une classe'
                ], 403);
            }

            $classe = Classe::find($id);
            if (!$classe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Classe non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'niveau' => 'sometimes|string|max:50',
                'capacite' => 'sometimes|integer|min:1|max:50',
                'description' => 'nullable|string|max:500',
                'annee_scolaire' => 'sometimes|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $classe->update($request->only(['nom', 'niveau', 'capacite', 'description', 'annee_scolaire']));
            Log::info('Classe mise à jour avec succès');

            return response()->json([
                'status' => 'success',
                'message' => 'Classe mise à jour avec succès',
                'data' => $classe->load(['matieres', 'eleves.user'])
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour classe:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de la classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer une classe
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer une classe'
                ], 403);
            }

            $classe = Classe::find($id);
            if (!$classe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Classe non trouvée'
                ], 404);
            }

            // Vérifier s'il y a des élèves dans la classe
            if ($classe->eleves()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de supprimer une classe contenant des élèves'
                ], 400);
            }

            $classe->delete();
            Log::info('Classe supprimée avec succès:', ['id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Classe supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur suppression classe:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

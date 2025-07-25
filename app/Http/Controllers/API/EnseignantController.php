<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;



use App\Models\Enseignant;
use App\Services\EnseignantService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnseignantController extends Controller
{
    protected $enseignantService;
    protected $userService;

    public function __construct(EnseignantService $enseignantService, UserService $userService)
    {
        $this->enseignantService = $enseignantService;
        $this->userService = $userService;
    }

    /**
     * Liste des enseignants
     */
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $enseignants = Enseignant::with(['user', 'matieres', 'classes'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $enseignants
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des enseignants',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer un nouvel enseignant
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut créer un enseignant'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'telephone' => 'required|string|max:20|unique:users,telephone',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'specialite' => 'required|string|max:255',
                'date_embauche' => 'required|date',
                'numero_identifiant' => 'required|string|max:50|unique:enseignants,numero_identifiant',
                'classe_ids' => 'nullable|array',
                'classe_ids.*' => 'exists:classes,id',
                'matiere_ids' => 'nullable|array',
                'matiere_ids.*' => 'exists:matieres,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userData = $request->only(['nom', 'prenom', 'adresse', 'telephone', 'email', 'password']);
            $userData['role'] = 'enseignant';
            $userData['classe_ids'] = $request->classe_ids;
            $userData['matiere_ids'] = $request->matiere_ids;

            $enseignant = $this->userService->createEnseignant($userData);

            return response()->json([
                'status' => 'success',
                'message' => 'Enseignant créé avec succès',
                'data' => $enseignant->load(['user', 'matieres', 'classes'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'enseignant',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher un enseignant spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $enseignant = Enseignant::with(['user', 'matieres', 'classes'])->find($id);

            if (!$enseignant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $enseignant
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'enseignant',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour un enseignant
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut modifier un enseignant'
                ], 403);
            }

            $enseignant = Enseignant::find($id);
            if (!$enseignant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'adresse' => 'sometimes|string|max:255',
                'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $enseignant->user_id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $enseignant->user_id,
                'specialite' => 'sometimes|string|max:255',
                'date_embauche' => 'sometimes|date',
                'numero_identifiant' => 'sometimes|string|max:50|unique:enseignants,numero_identifiant,' . $id,
                'classe_ids' => 'nullable|array',
                'classe_ids.*' => 'exists:classes,id',
                'matiere_ids' => 'nullable|array',
                'matiere_ids.*' => 'exists:matieres,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Mettre à jour l'utilisateur
            $enseignant->user->update($request->only(['nom', 'prenom', 'adresse', 'telephone', 'email']));

            // Mettre à jour l'enseignant
            $enseignant->update($request->only(['specialite', 'date_embauche', 'numero_identifiant']));

            // Mettre à jour les affectations
            if ($request->has('classe_ids')) {
                $this->enseignantService->affecterClasses($enseignant, $request->classe_ids);
            }

            if ($request->has('matiere_ids')) {
                $this->enseignantService->affecterMatieres($enseignant, $request->matiere_ids);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Enseignant mis à jour avec succès',
                'data' => $enseignant->load(['user', 'matieres', 'classes'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de l\'enseignant',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer un enseignant
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer un enseignant'
                ], 403);
            }

            $enseignant = Enseignant::find($id);
            if (!$enseignant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enseignant non trouvé'
                ], 404);
            }

            // Vérifier s'il y a des notes associées
            if ($enseignant->notes()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de supprimer un enseignant ayant des notes'
                ], 400);
            }

            // Supprimer l'enseignant et son utilisateur
            $enseignant->user->delete();
            $enseignant->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Enseignant supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'enseignant',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

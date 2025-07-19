<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Services\ClasseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

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

            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $classes = Classe::with(['matieres', 'eleves.user'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $classes
            ], 200);

        } catch (\Exception $e) {
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
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $classe = Classe::create($request->all());

            // Affectation automatique des matières
            if ($request->affectation_automatique) {
                $this->classeService->affectationAutomatiqueMatieres($classe);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Classe créée avec succès',
                'data' => $classe->load(['matieres', 'eleves.user'])
            ], 201);

        } catch (\Exception $e) {
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $classe->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Classe mise à jour avec succès',
                'data' => $classe->load(['matieres', 'eleves.user'])
            ], 200);

        } catch (\Exception $e) {
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

            return response()->json([
                'status' => 'success',
                'message' => 'Classe supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

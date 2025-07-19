<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Matiere;
use App\Services\MatiereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class MatiereController extends Controller
{
    protected $matiereService;

    public function __construct(MatiereService $matiereService)
    {
        $this->matiereService = $matiereService;
    }

    /**
     * Liste des matières
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

            $matieres = Matiere::with(['classes', 'enseignants'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $matieres
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des matières',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer une nouvelle matière
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut créer une matière'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'niveau' => 'required|in:college,lycee,tous',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $matiere = $this->matiereService->createMatiere($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Matière créée avec succès',
                'data' => $matiere->load(['classes', 'enseignants'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la matière',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher une matière spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $matiere = Matiere::with(['classes', 'enseignants'])->find($id);

            if (!$matiere) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Matière non trouvée'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $matiere
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la matière',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour une matière
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut modifier une matière'
                ], 403);
            }

            $matiere = Matiere::find($id);
            if (!$matiere) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Matière non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'niveau' => 'sometimes|required|in:college,lycee,tous',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $matiere = $this->matiereService->updateMatiere($matiere, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Matière mise à jour avec succès',
                'data' => $matiere->load(['classes', 'enseignants'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de la matière',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer une matière
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer une matière'
                ], 403);
            }

            $matiere = Matiere::find($id);
            if (!$matiere) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Matière non trouvée'
                ], 404);
            }

            // Vérifier si la matière est utilisée
            if ($matiere->classes()->count() > 0 || $matiere->notes()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de supprimer une matière utilisée'
                ], 400);
            }

            $matiere->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Matière supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la matière',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

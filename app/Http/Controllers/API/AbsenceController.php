<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Eleve;
use App\Services\AbsenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AbsenceController extends Controller
{
    protected $absenceService;

    public function __construct(AbsenceService $absenceService)
    {
        $this->absenceService = $absenceService;
    }

    /**
     * Liste des absences
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

            $absences = Absence::with(['eleve.user'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $absences
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des absences',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer une nouvelle absence
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut créer une absence'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'eleve_id' => 'required|exists:eleves,id',
                'date_absence' => 'required|date',
                'periode' => 'required|in:matin,apres_midi,journee',
                'motif' => 'required|string|max:255',
                'est_justifiee' => 'boolean',
                'document_justificatif' => 'nullable|string',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $absence = Absence::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Absence créée avec succès',
                'data' => $absence->load(['eleve.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'absence',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher une absence spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $absence = Absence::with(['eleve.user'])->find($id);

            if (!$absence) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Absence non trouvée'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $absence
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'absence',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour une absence
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut modifier une absence'
                ], 403);
            }

            $absence = Absence::find($id);
            if (!$absence) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Absence non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'date_absence' => 'sometimes|date',
                'periode' => 'sometimes|in:matin,apres_midi,journee',
                'motif' => 'sometimes|string|max:255',
                'est_justifiee' => 'sometimes|boolean',
                'document_justificatif' => 'nullable|string',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $absence->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Absence mise à jour avec succès',
                'data' => $absence->load(['eleve.user'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de l\'absence',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer une absence
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer une absence'
                ], 403);
            }

            $absence = Absence::find($id);
            if (!$absence) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Absence non trouvée'
                ], 404);
            }

            $absence->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Absence supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'absence',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Obtenir les absences d'un élève
     */
    public function getEleveAbsences(string $eleveId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $eleve = Eleve::find($eleveId);
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

            $absences = Absence::where('eleve_id', $eleveId)
                ->with(['eleve.user'])
                ->orderBy('date_absence', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $absences
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des absences',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
} 
<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Eleve;
use App\Services\NoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class NoteController extends Controller
{
    protected $noteService;

    public function __construct(NoteService $noteService)
    {
        $this->noteService = $noteService;
    }

    /**
     * Liste des notes avec contrôle d'accès
     */
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Admin et enseignant : toutes les notes
            if ($user->isAdmin() || $user->isEnseignant()) {
                $notes = Note::with(['eleve.user', 'matiere', 'enseignant.user'])->get();
            }
            // Élève : ses propres notes uniquement
            elseif ($user->isEleve()) {
                $notes = Note::where('eleve_id', $user->eleve->id)
                    ->with(['eleve.user', 'matiere', 'enseignant.user'])
                    ->get();
            }
            // Parent : notes de ses enfants uniquement
            elseif ($user->isParent()) {
                $enfantsIds = $user->parentUser->eleves->pluck('id');
                $notes = Note::whereIn('eleve_id', $enfantsIds)
                    ->with(['eleve.user', 'matiere', 'enseignant.user'])
                    ->get();
            }
            else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $notes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher une note spécifique avec contrôle d'accès
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $note = Note::with(['eleve.user', 'matiere', 'enseignant.user'])->find($id);

            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note non trouvée'
                ], 404);
            }

            // Vérification des permissions
            $canAccess = false;

            if ($user->isAdmin() || $user->isEnseignant()) {
                $canAccess = true;
            }
            elseif ($user->isEleve() && $user->eleve->id == $note->eleve_id) {
                $canAccess = true;
            }
            elseif ($user->isParent()) {
                $enfantsIds = $user->parentUser->eleves->pluck('id');
                $canAccess = $enfantsIds->contains($note->eleve_id);
            }

            if (!$canAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé à cette note'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $note
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la note',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer une nouvelle note
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un enseignant peut saisir une note'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'eleve_id' => 'required|exists:eleves,id',
                'matiere_id' => 'required|exists:matieres,id',
                'valeur' => 'required|numeric|between:0,20',
                'type_note' => 'required|in:devoir,composition,interrogation,oral',
                'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['enseignant_id'] = $user->enseignant->id;

            $note = $this->noteService->saisirNote($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Note saisie avec succès',
                'data' => $note->load(['eleve.user', 'matiere', 'enseignant.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la saisie de la note',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour une note
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un enseignant peut modifier une note'
                ], 403);
            }

            $note = Note::find($id);
            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'valeur' => 'sometimes|numeric|between:0,20',
                'type_note' => 'sometimes|in:devoir,composition,interrogation,oral',
                'periode' => 'sometimes|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $note->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Note mise à jour avec succès',
                'data' => $note->load(['eleve.user', 'matiere', 'enseignant.user'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de la note',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer une note
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un enseignant peut supprimer une note'
                ], 403);
            }

            $note = Note::find($id);
            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note non trouvée'
                ], 404);
            }

            $note->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Note supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la note',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Obtenir les notes d'un élève avec contrôle d'accès renforcé
     */
    public function getEleveNotes(string $eleveId)
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

            // Vérifier les permissions selon le rôle
            $canAccess = false;

            if ($user->isAdmin() || $user->isEnseignant()) {
                $canAccess = true;
            }
            elseif ($user->isEleve() && $user->eleve->id == $eleveId) {
                $canAccess = true;
            }
            elseif ($user->isParent()) {
                $enfantsIds = $user->parentUser->eleves->pluck('id');
                $canAccess = $enfantsIds->contains($eleveId);
            }

            if (!$canAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $notes = Note::where('eleve_id', $eleveId)
                ->with(['matiere', 'enseignant.user'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $notes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des notes',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Saisir une note pour un élève spécifique
     */
    public function storeEleveNote(Request $request, string $eleveId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un enseignant peut saisir une note'
                ], 403);
            }

            $eleve = Eleve::find($eleveId);
            if (!$eleve) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Élève non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'matiere_id' => 'required|exists:matieres,id',
                'valeur' => 'required|numeric|between:0,20',
                'type_note' => 'required|in:devoir,composition,interrogation,oral',
                'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['eleve_id'] = $eleveId;
            $data['enseignant_id'] = $user->enseignant->id;

            $note = $this->noteService->saisirNote($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Note saisie avec succès',
                'data' => $note->load(['eleve.user', 'matiere', 'enseignant.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la saisie de la note',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

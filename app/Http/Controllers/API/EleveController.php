<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Services\EleveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            
            // Vérifier les permissions
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $eleves = Eleve::with(['user', 'classe', 'parent.user'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $eleves
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des élèves',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut créer un élève'
                ], 403);
            }

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
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $eleveData = [
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'adresse' => $request->adresse,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'classe_id' => $request->classe_id,
            ];

            $parentData = [
                'nom' => $request->parent_nom,
                'prenom' => $request->parent_prenom,
                'adresse' => $request->parent_adresse,
                'telephone' => $request->parent_telephone,
                'email' => $request->parent_email,
                'profession' => $request->parent_profession,
            ];

            $eleve = $this->eleveService->inscrireEleve($eleveData, $parentData);

            return response()->json([
                'status' => 'success',
                'message' => 'Élève créé avec succès',
                'data' => $eleve->load(['user', 'classe', 'parent.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
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

            return response()->json([
                'status' => 'success',
                'message' => 'Élève mis à jour avec succès',
                'data' => $eleve->load(['user', 'classe', 'parent.user'])
            ], 200);

        } catch (\Exception $e) {
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

            // Supprimer l'élève et son utilisateur
            $eleve->user->delete();
            $eleve->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Élève supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

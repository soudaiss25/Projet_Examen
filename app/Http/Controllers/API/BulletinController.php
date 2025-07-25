<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bulletin;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\BulletinService;

class BulletinController extends Controller
{
    protected BulletinService $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Lister tous les bulletins (admin et enseignant uniquement)
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

            $bulletins = Bulletin::with(['eleve.user', 'classe'])
                ->orderBy('date_edition', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $bulletins
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des bulletins',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Générer un nouveau bulletin
     */
    public function store(Request $request)
    {
        return $this->generateBulletin($request, $request->eleve_id);
    }

    /**
     * Télécharger le PDF d'un bulletin
     */
    public function telecharger(int $eleveId, string $periode)
    {
        $eleve = Eleve::findOrFail($eleveId);

        $bulletin = $eleve
            ->bulletins()
            ->where('periode', $periode)
            ->firstOrFail();

        if (! $bulletin->pdf_path || ! Storage::disk('public')->exists($bulletin->pdf_path)) {
            abort(404, 'PDF non disponible');
        }

        return response()->file(
            storage_path("app/public/{$bulletin->pdf_path}")
        );
    }

    /**
     * Afficher un bulletin spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $bulletin = Bulletin::with(['eleve.user', 'classe'])->find($id);

            if (!$bulletin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bulletin non trouvé'
                ], 404);
            }

            if (
                !$user->isAdmin() && !$user->isEnseignant() &&
                ($user->isParent() && $user->parentUser->id !== $bulletin->eleve->parent_id) &&
                ($user->isEleve() && $user->id !== $bulletin->eleve->user_id)
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $bulletin
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du bulletin',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour un bulletin
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut modifier un bulletin'
                ], 403);
            }

            $bulletin = Bulletin::find($id);
            if (!$bulletin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bulletin non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'appreciation' => 'sometimes|string|max:1000',
                'mention' => 'sometimes|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bulletin->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Bulletin mis à jour avec succès',
                'data' => $bulletin->load(['eleve.user', 'classe'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du bulletin',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer un bulletin
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer un bulletin'
                ], 403);
            }

            $success = $this->bulletinService->supprimerBulletin($id);

            return $success
                ? response()->json(['status' => 'success', 'message' => 'Bulletin supprimé avec succès'], 200)
                : response()->json(['status' => 'error', 'message' => 'Erreur lors de la suppression du bulletin'], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du bulletin',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Obtenir les bulletins d'un élève (élève, parent, admin, enseignant)
     */
    public function getEleveBulletins(string $eleveId)
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

            if (
                !$user->isAdmin() && !$user->isEnseignant() &&
                ($user->isParent() && $user->parentUser->id !== $eleve->parent_id) &&
                ($user->isEleve() && $user->id !== $eleve->user_id)
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $bulletins = $this->bulletinService->getBulletinsEleve($eleveId);

            return response()->json([
                'status' => 'success',
                'data' => $bulletins
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des bulletins',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Générer un bulletin pour un élève spécifique
     */
    public function generateBulletin(Request $request, string $eleveId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut générer un bulletin'
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
                'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'annee_scolaire' => 'nullable|string|max:9',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bulletin = $this->bulletinService->genererBulletin(
                $eleveId,
                $request->periode,
                $request->annee_scolaire
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Bulletin généré avec succès',
                'data' => $bulletin
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la génération du bulletin',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DocumentEleve;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class DocumentController extends Controller
{
    /**
     * Liste des documents
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

            $documents = DocumentEleve::with(['eleve.user'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $documents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des documents',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Uploader un nouveau document
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut uploader un document'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'eleve_id' => 'required|exists:eleves,id',
                'type_document' => 'required|in:extrait_naissance,certificat_scolarite,photo,certificat_medical',
                'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents', $filename, 'public');

            $document = DocumentEleve::create([
                'eleve_id' => $request->eleve_id,
                'type_document' => $request->type_document,
                'chemin_fichier' => $path,
                'date_depot' => now(),
                'est_valide' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document uploadé avec succès',
                'data' => $document->load(['eleve.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'upload du document',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Afficher un document spécifique
     */
    public function show(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $document = DocumentEleve::with(['eleve.user'])->find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $document
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du document',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Mettre à jour un document
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut modifier un document'
                ], 403);
            }

            $document = DocumentEleve::find($id);
            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'est_valide' => 'sometimes|boolean',
                'type_document' => 'sometimes|in:extrait_naissance,certificat_scolarite,photo,certificat_medical',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $document->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Document mis à jour avec succès',
                'data' => $document->load(['eleve.user'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du document',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Supprimer un document
     */
    public function destroy(string $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur peut supprimer un document'
                ], 403);
            }

            $document = DocumentEleve::find($id);
            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document non trouvé'
                ], 404);
            }

            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($document->chemin_fichier)) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }

            $document->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Document supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du document',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Obtenir les documents d'un élève
     */
    public function getEleveDocuments(string $eleveId)
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

            $documents = DocumentEleve::where('eleve_id', $eleveId)
                ->with(['eleve.user'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $documents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des documents',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Uploader un document pour un élève spécifique
     */
    public function uploadDocument(Request $request, string $eleveId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isAdmin() && !$user->isEnseignant()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seul un administrateur ou enseignant peut uploader un document'
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
                'type_document' => 'required|in:extrait_naissance,certificat_scolarite,photo,certificat_medical',
                'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents', $filename, 'public');

            $document = DocumentEleve::create([
                'eleve_id' => $eleveId,
                'type_document' => $request->type_document,
                'chemin_fichier' => $path,
                'date_depot' => now(),
                'est_valide' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document uploadé avec succès',
                'data' => $document->load(['eleve.user'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'upload du document',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
}

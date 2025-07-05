<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users,telephone|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:' . implode(',', [
                    User::ROLE_ADMIN,
                    User::ROLE_ENSEIGNANT,
                    User::ROLE_PARENT,
                    User::ROLE_ELEVE,
                ]),
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = $request->only(['nom', 'prenom', 'adresse', 'telephone', 'email']);
            $userData['password'] = Hash::make($request->password);
            $userData['role'] = $request->role ?? User::ROLE_ELEVE;
            $userData['est_actif'] = true;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('users', 'public');
                $userData['image'] = $imagePath;
            }

            $user = User::create($userData);

            // Crée un token JWT avec une durée d'expiration
            $token = JWTAuth::fromUser($user);
            $expiresIn = config('jwt.ttl', 60) * 60; // Convertir en secondes

            return response()->json([
                'status' => 'success',
                'message' => 'Utilisateur créé avec succès',
                'data' => [
                    'user' => $user->only(['id', 'nom', 'prenom', 'email', 'role', 'telephone', 'adresse', 'est_actif']),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $expiresIn
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            $user = JWTAuth::user();

            if (!$user->isActive()) {
                // Invalider le token si l'utilisateur n'est pas actif
                JWTAuth::invalidate($token);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Compte désactivé. Contactez l\'administrateur.'
                ], 403);
            }

            $expiresIn = config('jwt.ttl', 60) * 60; // Convertir en secondes

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => $user->only(['id', 'nom', 'prenom', 'email', 'role', 'telephone', 'adresse', 'est_actif']),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $expiresIn
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la génération du token',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la connexion',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout()
    {
        try {
            // Récupérer le token et l'invalider
            $token = JWTAuth::getToken();
            JWTAuth::invalidate($token);

            return response()->json([
                'status' => 'success',
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la déconnexion',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user->only(['id', 'nom', 'prenom', 'email', 'role', 'telephone', 'adresse', 'est_actif', 'image']),
                    'permissions' => [
                        'is_admin' => $user->isAdmin(),
                        'is_enseignant' => $user->isEnseignant(),
                        'is_parent' => $user->isParent(),
                        'is_eleve' => $user->isEleve(),
                    ]
                ]
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token manquant'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Rafraîchir le token JWT
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh();
            $expiresIn = config('jwt.ttl', 60) * 60; // Convertir en secondes

            return response()->json([
                'status' => 'success',
                'message' => 'Token rafraîchi avec succès',
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => $expiresIn
                ]
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré et non rafraîchissable'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du rafraîchissement du token',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Mise à jour du profil utilisateur
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'adresse' => 'sometimes|string|max:255',
                'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $user->id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8|confirmed',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['nom', 'prenom', 'adresse', 'telephone', 'email']);

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('image')) {
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }
                $imagePath = $request->file('image')->store('users', 'public');
                $updateData['image'] = $imagePath;
            }

            $user->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'user' => $user->fresh()->only(['id', 'nom', 'prenom', 'email', 'role', 'telephone', 'adresse', 'est_actif', 'image'])
                ]
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token manquant'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Changer le statut d'activation d'un utilisateur (Admin seulement)
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        try {
            $authUser = JWTAuth::parseToken()->authenticate();

            if (!$authUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $user = User::findOrFail($userId);

            if ($user->est_actif) {
                $user->desactiver();
            } else {
                $user->activer();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Statut utilisateur mis à jour',
                'data' => [
                    'user' => $user->only(['id', 'nom', 'email', 'est_actif'])
                ]
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token manquant'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Invalider tous les tokens d'un utilisateur
     */
    public function invalidateAllTokens()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Invalider tous les tokens de l'utilisateur
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'Tous les tokens ont été invalidés'
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token manquant'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'invalidation des tokens',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed  ...$roles  Liste des rôles acceptés pour la route
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Vérifie si l'utilisateur est authentifié et actif
        if (!$user || !$user->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Compte inactif ou utilisateur non authentifié.'
            ], 403);
        }

        // Vérifie si l'utilisateur a un des rôles demandés via les méthodes du modèle
        foreach ($roles as $role) {
            // Formate la méthode : ex. "admin" => "isAdmin"
            $method = 'is' . ucfirst(strtolower($role));
            if (method_exists($user, $method) && $user->$method()) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Accès non autorisé. Rôle requis : ' . implode(' ou ', $roles),
        ], 403);
    }
}

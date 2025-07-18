<?php

namespace App\Http\Controllers\API;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function createEleve(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'adresse' => 'required|string',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'classe_id' => 'required|exists:classes,id',
            // Parent
            'parent_nom' => 'required|string',
            'parent_prenom' => 'required|string',
            'parent_telephone' => 'required|string',
            'parent_adresse' => 'required|string',
            'parent_email' => 'required|email',
        ]);

        $user = UserService::createEleve($data);

        return response()->json($user->load('eleve'));
    }

    public function createEnseignant(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'adresse' => 'required|string',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'exists:classes,id',
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:matieres,id',
        ]);

        $user = UserService::createEnseignant($data);

        return response()->json($user->load('enseignant'));
    }

    public function createParent(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'adresse' => 'required|string',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = UserService::createParent($data);

        return response()->json($user->load('parentUser'));
    }
}

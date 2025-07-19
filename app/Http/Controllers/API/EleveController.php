<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;


use App\Services\EleveService;
use Illuminate\Http\Request;

class EleveController extends Controller
{
    protected $eleveService;

    public function __construct(EleveService $eleveService)
    {
        $this->eleveService = $eleveService;
    }

    public function inscrire(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'adresse' => 'required|string',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:masculin,féminin',
            'classe_id' => 'required|exists:classes,id',

            // Parent
            'parent.nom' => 'required|string',
            'parent.prenom' => 'required|string',
            'parent.telephone' => 'required|string',
            'parent.adresse' => 'required|string',
            'parent.email' => 'required|email',
            'parent.password' => 'nullable|string',
            'parent.profession' => 'nullable|string',
        ]);

        $eleve = $this->eleveService->inscrireEleve($data, $data['parent']);

        return response()->json($eleve->load('user'), 201);
    }
}

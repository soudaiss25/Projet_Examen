<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;


use App\Models\Enseignant;
use App\Services\EnseignantService;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    protected $enseignantService;

    public function __construct(EnseignantService $enseignantService)
    {
        $this->enseignantService = $enseignantService;
    }

    public function affecterMatieres(Request $request, Enseignant $enseignant)
    {
        $request->validate([
            'matiere_ids' => 'required|array',
            'matiere_ids.*' => 'exists:matieres,id',
        ]);

        $enseignant = $this->enseignantService->affecterMatieres($enseignant, $request->matiere_ids);

        return response()->json($enseignant);
    }

    public function affecterClasses(Request $request, Enseignant $enseignant)
    {
        $request->validate([
            'classe_ids' => 'required|array',
            'classe_ids.*' => 'exists:classes,id',
        ]);

        $enseignant = $this->enseignantService->affecterClasses($enseignant, $request->classe_ids);

        return response()->json($enseignant);
    }

    public function affectationAutomatique(Enseignant $enseignant)
    {
        $enseignant = $this->enseignantService->affectationAutomatique($enseignant);

        return response()->json($enseignant->load('matieres'));
    }
}

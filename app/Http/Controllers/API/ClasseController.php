<?php

namespace App\Http\Controllers\API;

use App\Models\Classe;
use App\Services\ClasseService;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    protected $classeService;

    public function __construct(ClasseService $classeService)
    {
        $this->classeService = $classeService;
    }

    public function affecterMatieres(Request $request, Classe $classe)
    {
        $request->validate([
            'matieres' => 'required|array',
            'matieres.*.matiere_id' => 'required|exists:matieres,id',
            'matieres.*.coefficient' => 'required|integer|min:1',
        ]);

        $classe = $this->classeService->affecterMatieres($classe, $request->matieres);

        return response()->json($classe);
    }

    public function affectationAutomatique(Classe $classe)
    {
        $classe = $this->classeService->affectationAutomatiqueMatieres($classe);

        return response()->json($classe->load('matieres'));
    }
}

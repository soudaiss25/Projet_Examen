<?php

namespace App\Http\Controllers;

use App\Models\ClasseMatiere;
use Illuminate\Http\Request;

class ClasseMatiereController extends Controller
{

    public function index()
    {
        return ClasseMatiere::with(['classe', 'matiere'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'coefficient' => 'nullable|integer|min:1',
        ]);

        $classeMatiere = ClasseMatiere::create($request->all());

        return response()->json($classeMatiere, 201);
    }


    public function show($id)
    {
        $classeMatiere = ClasseMatiere::with(['classe', 'matiere'])->findOrFail($id);
        return response()->json($classeMatiere);
    }


    public function update(Request $request, $id)
    {
        $classeMatiere = ClasseMatiere::findOrFail($id);

        $request->validate([
            'classe_id' => 'sometimes|exists:classes,id',
            'matiere_id' => 'sometimes|exists:matieres,id',
            'coefficient' => 'nullable|integer|min:1',
        ]);

        $classeMatiere->update($request->all());

        return response()->json($classeMatiere);
    }


    public function destroy($id)
    {
        $classeMatiere = ClasseMatiere::findOrFail($id);
        $classeMatiere->delete();

        return response()->json(['message' => 'Association supprimée avec succès']);
    }
}

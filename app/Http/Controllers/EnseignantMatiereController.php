<?php

namespace App\Http\Controllers;

use App\Models\EnseignantMatiere;
use Illuminate\Http\Request;

class EnseignantMatiereController extends Controller
{
    public function index()
    {
        return EnseignantMatiere::with(['enseignant', 'matiere'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'matiere_id' => 'required|exists:matieres,id',
        ]);

        $data = EnseignantMatiere::create($request->all());

        return response()->json($data, 201);
    }

    public function show($id)
    {
        return EnseignantMatiere::with(['enseignant', 'matiere'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $data = EnseignantMatiere::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'sometimes|exists:enseignants,id',
            'matiere_id' => 'sometimes|exists:matieres,id',
        ]);

        $data->update($request->all());

        return response()->json($data);
    }

    public function destroy($id)
    {
        EnseignantMatiere::findOrFail($id)->delete();
        return response()->json(['message' => 'Association supprimée']);
    }
}

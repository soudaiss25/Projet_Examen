<?php

namespace App\Http\Controllers;

use App\Models\EnseignantClasse;
use Illuminate\Http\Request;

class EnseignantClasseController extends Controller
{
    public function index()
    {
        return EnseignantClasse::with(['enseignant', 'classe'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'classe_id' => 'required|exists:classes,id',
        ]);

        $data = EnseignantClasse::create($request->all());

        return response()->json($data, 201);
    }

    public function show($id)
    {
        return EnseignantClasse::with(['enseignant', 'classe'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $data = EnseignantClasse::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'sometimes|exists:enseignants,id',
            'classe_id' => 'sometimes|exists:classes,id',
        ]);

        $data->update($request->all());

        return response()->json($data);
    }

    public function destroy($id)
    {
        EnseignantClasse::findOrFail($id)->delete();
        return response()->json(['message' => 'Association supprimée']);
    }
}

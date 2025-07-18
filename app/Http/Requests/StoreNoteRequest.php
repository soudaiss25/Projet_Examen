<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ou appliquer une logique d'autorisation ici
    }

    public function rules(): array
    {
        return [
            'eleve_id' => 'required|exists:eleves,id',
            'matiere_id' => 'required|exists:matieres,id',
            'enseignant_id' => 'required|exists:enseignants,id',
            'valeur' => 'required|numeric|min:0|max:20',
            'type_note' => 'required|in:devoir,composition,interrogation,oral',
            'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
            'commentaire' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'eleve_id.required' => 'L\'élève est requis.',
            'valeur.max' => 'La note ne peut pas dépasser 20.',
            // Autres messages personnalisés si souhaité
        ];
    }
}

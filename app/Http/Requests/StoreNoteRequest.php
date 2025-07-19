<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autorise la requête pour les utilisateurs authentifiés
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<string>|string>
     */
    public function rules(): array
    {
        return [
            'eleve_id'      => ['required', 'integer', 'exists:eleves,id'],
            'matiere_id'    => ['required', 'integer', 'exists:matieres,id'],
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'valeur'        => ['required', 'numeric', 'between:0,20'],
            'type_note'     => ['required', 'string', 'in:devoir,composition,interrogation,oral'],
            'periode'       => ['required', 'string', 'in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2'],
            'commentaire'   => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eleve_id.required'      => 'L\'élève est requis.',
            'matiere_id.required'    => 'La matière est requise.',
            'enseignant_id.required' => 'L\'enseignant est requis.',
            'valeur.numeric'         => 'La note doit être un nombre.',
            'valeur.between'         => 'La note doit être comprise entre 0 et 20.',
            'type_note.in'           => 'Le type de note doit être valide (devoir, composition, interrogation ou oral).',
            'periode.in'             => 'La période doit être valide (ex. trimestre_1, semestre_2).',
            'commentaire.max'        => 'Le commentaire ne peut pas dépasser 255 caractères.',
        ];
    }
}

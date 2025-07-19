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
        // Autorise tout utilisateur authentifié (ou adapte selon tes besoins)
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
            'eleve_id'   => ['required', 'integer', 'exists:eleves,id'],
            'matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            'periode'    => ['required', 'string'],
            'type_note'  => ['required', 'string'],
            'valeur'     => ['required', 'numeric', 'between:0,20'],
        ];
    }
}

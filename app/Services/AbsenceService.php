<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Eleve;
use Illuminate\Support\Facades\Validator;
use Exception;

class AbsenceService
{
    /**
     * Enregistrer une absence pour un élève
     */
    public function enregistrerAbsence(array $data): Absence
    {
        $validator = Validator::make($data, [
            'eleve_id' => 'required|exists:eleves,id',
            'date_absence' => 'required|date',
            'periode' => 'required|in:matin,apres_midi,journee',
            'motif' => 'nullable|string',
            'est_justifiee' => 'boolean',
            'document_justificatif' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $validatedData = $validator->validated();
        $validatedData['est_justifiee'] = $validatedData['est_justifiee'] ?? false;

        return Absence::create($validatedData);
    }

    /**
     * Justifier une absence
     */
    public function justifierAbsence(Absence $absence, array $data): Absence
    {
        $validator = Validator::make($data, [
            'motif' => 'required|string',
            'document_justificatif' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $absence->update([
            'motif' => $data['motif'],
            'est_justifiee' => true,
            'document_justificatif' => $data['document_justificatif'] ?? null,
        ]);

        return $absence;
    }

    /**
     * Récupérer les absences d'un élève pour une période donnée
     */
    public function getAbsencesElevePeriode($eleveId, $dateDebut, $dateFin)
    {
        return Absence::where('eleve_id', $eleveId)
            ->whereBetween('date_absence', [$dateDebut, $dateFin])
            ->get();
    }

    /**
     * Calculer le nombre total d'absences d'un élève
     */
    public function getTotalAbsencesEleve($eleveId)
    {
        return Absence::where('eleve_id', $eleveId)->count();
    }
}

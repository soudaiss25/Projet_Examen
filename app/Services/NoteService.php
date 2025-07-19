<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\Enseignant;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
use Illuminate\Support\Facades\Validator;
use Exception;
=======
use Illuminate\Support\Collection;
>>>>>>> Lotita

class NoteService
{
    /**
     * Saisir une note pour un élève dans une matière spécifique
     */
    public function saisirNote(array $data): Note
    {
        return DB::transaction(function () use ($data) {
            $validator = Validator::make($data, [
                'eleve_id' => 'required|exists:eleves,id',
                'matiere_id' => 'required|exists:matieres,id',
                'enseignant_id' => 'required|exists:enseignants,id',
                'valeur' => 'required|numeric|between:0,20',
                'type_note' => 'required|in:devoir,composition,interrogation,oral',
                'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $validatedData = $validator->validated();

            // Vérifier que l'enseignant est autorisé
            $enseignant = Enseignant::findOrFail($validatedData['enseignant_id']);
            $eleve = Eleve::findOrFail($validatedData['eleve_id']);
            $matiere = Matiere::findOrFail($validatedData['matiere_id']);

            if (!$enseignant->matieres()->where('matiere_id', $matiere->id)->exists() ||
                !$enseignant->classes()->where('classe_id', $eleve->classe_id)->exists()) {
                throw new Exception('L\'enseignant n\'est pas autorisé à saisir cette note.');
            }

            return Note::create($validatedData);
        });
    }

<<<<<<< HEAD
    /**
     * Récupérer les notes d'un élève pour une période donnée
     */
    public function getNotesElevePeriode($eleveId, $periode)
    {
        return Note::where('eleve_id', $eleveId)
=======
    protected function getOrCreateBulletin(int $eleveId, string $periode): Bulletin
    {
        $classeId = DB::table('eleves')->where('id', $eleveId)->value('classe_id');
        $anneeScolaire = date('Y') . '-' . (date('Y') + 1);

        return Bulletin::firstOrCreate([
            'eleve_id'      => $eleveId,
            'periode'       => $periode,
        ], [
            'classe_id'     => $classeId,
            'annee_scolaire'=> $anneeScolaire,
        ]);
    }

    public function getNotesEleve(int $eleveId, string $periode)
    {
        return Note::with(['matiere', 'enseignant'])
            ->where('eleve_id', $eleveId)
>>>>>>> Lotita
            ->where('periode', $periode)
            ->with(['matiere', 'enseignant.user'])
            ->get();
    }

    /**
<<<<<<< HEAD
     * Calculer la moyenne d'un élève dans une matière pour une période donnée
     */
    public function calculerMoyenneMatiere($eleveId, $matiereId, $periode)
    {
        $notes = Note::where('eleve_id', $eleveId)
            ->where('matiere_id', $matiereId)
            ->where('periode', $periode)
            ->get();

        if ($notes->isEmpty()) {
            return null;
        }

        $total = $notes->sum('valeur');
        return $total / $notes->count();
=======
     * Calcule la moyenne générale à partir d'une collection de notes.
     *
     * @param Collection<int, Note> $notes
     */
    public function calculerMoyenneGenerale(Collection $notes): float
    {
        return round($notes->avg('valeur'), 2);
    }

    /**
     * Calcule la moyenne par matière à partir d'une collection de notes.
     * Retourne un tableau associatif ["Matière" => moyenne].
     *
     * @param Collection<int, Note> $notes
     */
    public function calculerMoyennesParMatiere(Collection $notes): array
    {
        return $notes
            ->groupBy(fn(Note $note) => $note->matiere->libelle ?? $note->matiere_id)
            ->map(fn(Collection $group) => round($group->avg('valeur'), 2))
            ->toArray();
    }

    /**
     * Détermine la mention selon la moyenne générale.
     */
    public function mention(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default        => 'Insuffisant',
        };
    }

    /**
     * Fournit une appréciation textuelle selon la moyenne générale.
     */
    public function appreciation(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent travail, continuez ainsi.',
            $moyenne >= 14 => 'Très bon travail, peut encore progresser.',
            $moyenne >= 12 => 'Bon ensemble, attention à certaines matières.',
            $moyenne >= 10 => 'Résultats acceptables, des efforts sont attendus.',
            default        => 'Résultats insuffisants, doit redoubler d’efforts.',
        };
>>>>>>> Lotita
    }
}

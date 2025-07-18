<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Bulletin;
use Illuminate\Support\Facades\DB;

class NoteService
{
    public function saisirNote(array $data): Note|string
    {
        $existing = Note::where('eleve_id', $data['eleve_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->where('periode', $data['periode'])
            ->where('type_note', $data['type_note'])
            ->first();

        if ($existing) {
            return 'Une note existe déjà pour cet élève, matière, période et type de note.';
        }

        $bulletin = $this->getOrCreateBulletin($data['eleve_id'], $data['periode']);
        $data['bulletin_id'] = $bulletin->id;

        return Note::create($data);
    }

    protected function getOrCreateBulletin($eleveId, $periode): Bulletin
    {
        $classeId = DB::table('eleves')->where('id', $eleveId)->value('classe_id');
        $anneeScolaire = date('Y') . '-' . (date('Y') + 1);

        return Bulletin::firstOrCreate([
            'eleve_id' => $eleveId,
            'periode' => $periode,
        ], [
            'classe_id' => $classeId,
            'annee_scolaire' => $anneeScolaire,
        ]);
    }

    public function getNotesEleve($eleveId, $periode)
    {
        return Note::with(['matiere', 'enseignant'])
            ->where('eleve_id', $eleveId)
            ->where('periode', $periode)
            ->get();
    }

    public function moyenneGenerale($eleveId, $periode): float
    {
        return round(Note::where('eleve_id', $eleveId)
            ->where('periode', $periode)
            ->avg('valeur'), 2);
    }

    public function mention($moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }

    public function appreciation($moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent travail, continuez ainsi.',
            $moyenne >= 14 => 'Très bon travail, peut encore progresser.',
            $moyenne >= 12 => 'Bon ensemble, attention à certaines matières.',
            $moyenne >= 10 => 'Résultats acceptables, des efforts sont attendus.',
            default => 'Résultats insuffisants, doit redoubler d’efforts.',
        };
    }
}

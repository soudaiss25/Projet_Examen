<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\Enseignant;
use App\Models\Bulletin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Collection;

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

            // Vérification des autorisations de l'enseignant
            if (!$enseignant->matieres()->where('matiere_id', $matiere->id)->exists() ||
                !$enseignant->classes()->where('classe_id', $eleve->classe_id)->exists()) {
                throw new Exception('L\'enseignant n\'est pas autorisé à saisir cette note.');
            }

            return Note::create($validatedData);
        });
    }

    /**
     * Récupérer les notes d'un élève pour une période donnée
     */
    public function getNotesElevePeriode(int $eleveId, string $periode): Collection
    {
        return Note::where('eleve_id', $eleveId)
            ->where('periode', $periode)
            ->with(['matiere', 'enseignant.user'])
            ->get();
    }

    /**
     * Récupérer toutes les notes d'un élève
     */
    public function getNotesEleve(int $eleveId, ?string $periode = null): Collection
    {
        $query = Note::with(['matiere', 'enseignant.user'])
            ->where('eleve_id', $eleveId);

        if ($periode) {
            $query->where('periode', $periode);
        }

        return $query->get();
    }

    /**
     * Calculer la moyenne d'un élève dans une matière pour une période donnée
     */
    public function calculerMoyenneMatiere(int $eleveId, int $matiereId, string $periode): ?float
    {
        $notes = Note::where('eleve_id', $eleveId)
            ->where('matiere_id', $matiereId)
            ->where('periode', $periode)
            ->get();

        if ($notes->isEmpty()) {
            return null;
        }

        $total = $notes->sum('valeur');
        return round($total / $notes->count(), 2);
    }

    /**
     * Calcule la moyenne générale à partir d'une collection de notes
     */
    public function calculerMoyenneGenerale(Collection $notes): float
    {
        if ($notes->isEmpty()) {
            return 0.0;
        }

        return round($notes->avg('valeur'), 2);
    }

    /**
     * Calcule la moyenne par matière à partir d'une collection de notes
     * Retourne un tableau associatif ["Matière" => moyenne]
     */
    public function calculerMoyennesParMatiere(Collection $notes): array
    {
        if ($notes->isEmpty()) {
            return [];
        }

        return $notes
            ->groupBy(function (Note $note) {
                return $note->matiere->libelle ?? 'Matière ' . $note->matiere_id;
            })
            ->map(function (Collection $group) {
                return round($group->avg('valeur'), 2);
            })
            ->toArray();
    }

    /**
     * Calcule la moyenne générale d'un élève pour une période
     */
    public function calculerMoyenneElevePeriode(int $eleveId, string $periode): float
    {
        $notes = $this->getNotesElevePeriode($eleveId, $periode);
        return $this->calculerMoyenneGenerale($notes);
    }

    /**
     * Détermine la mention selon la moyenne générale
     */
    public function getMention(float $moyenne): string
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
     * Fournit une appréciation textuelle selon la moyenne générale
     */
    public function getAppreciation(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent travail, continuez ainsi.',
            $moyenne >= 14 => 'Très bon travail, peut encore progresser.',
            $moyenne >= 12 => 'Bon ensemble, attention à certaines matières.',
            $moyenne >= 10 => 'Résultats acceptables, des efforts sont attendus.',
            default        => 'Résultats insuffisants, doit redoubler d\'efforts.',
        };
    }

    /**
     * Obtient ou crée un bulletin pour un élève et une période
     */
    protected function getOrCreateBulletin(int $eleveId, string $periode): Bulletin
    {
        $eleve = Eleve::findOrFail($eleveId);
        $anneeScolaire = date('Y') . '-' . (date('Y') + 1);

        return Bulletin::firstOrCreate([
            'eleve_id' => $eleveId,
            'periode'  => $periode,
        ], [
            'classe_id'      => $eleve->classe_id,
            'annee_scolaire' => $anneeScolaire,
        ]);
    }

    /**
     * Génère un rapport complet des notes d'un élève pour une période
     */
    public function genererRapportEleve(int $eleveId, string $periode): array
    {
        $notes = $this->getNotesElevePeriode($eleveId, $periode);
        $moyenneGenerale = $this->calculerMoyenneGenerale($notes);
        $moyennesParMatiere = $this->calculerMoyennesParMatiere($notes);

        return [
            'eleve_id' => $eleveId,
            'periode' => $periode,
            'notes' => $notes,
            'moyenne_generale' => $moyenneGenerale,
            'moyennes_par_matiere' => $moyennesParMatiere,
            'mention' => $this->getMention($moyenneGenerale),
            'appreciation' => $this->getAppreciation($moyenneGenerale),
            'nombre_notes' => $notes->count(),
        ];
    }

    /**
     * Valide qu'une note peut être modifiée ou supprimée
     */
    public function peutModifierNote(Note $note, int $enseignantId): bool
    {
        // Seul l'enseignant qui a saisi la note peut la modifier
        return $note->enseignant_id === $enseignantId;
    }

    /**
     * Supprime une note avec vérification des permissions
     */
    public function supprimerNote(int $noteId, int $enseignantId): bool
    {
        return DB::transaction(function () use ($noteId, $enseignantId) {
            $note = Note::findOrFail($noteId);

            if (!$this->peutModifierNote($note, $enseignantId)) {
                throw new Exception('Vous n\'êtes pas autorisé à supprimer cette note.');
            }

            return $note->delete();
        });
    }

    /**
     * Modifie une note avec vérification des permissions
     */
    public function modifierNote(int $noteId, array $data, int $enseignantId): Note
    {
        return DB::transaction(function () use ($noteId, $data, $enseignantId) {
            $note = Note::findOrFail($noteId);

            if (!$this->peutModifierNote($note, $enseignantId)) {
                throw new Exception('Vous n\'êtes pas autorisé à modifier cette note.');
            }

            $validator = Validator::make($data, [
                'valeur' => 'sometimes|numeric|between:0,20',
                'type_note' => 'sometimes|in:devoir,composition,interrogation,oral',
                'periode' => 'sometimes|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
                'commentaire' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $note->update($validator->validated());
            return $note->fresh(['matiere', 'enseignant.user', 'eleve.user']);
        });
    }
}

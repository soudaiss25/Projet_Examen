<?php



namespace App\Services;

use App\Models\Bulletin;
use App\Models\Note;
use App\Models\Eleve;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class BulletinService
{
    public function genererBulletin(Eleve $eleve, string $periode): Bulletin
    {
        $classe = $eleve->classe;
        $anneeScolaire = date('Y') . '-' . (date('Y') + 1);

        // Vérifie si un bulletin existe déjà
        $bulletin = Bulletin::firstOrCreate([
            'eleve_id' => $eleve->id,
            'classe_id' => $classe->id,
            'periode' => $periode,
            'annee_scolaire' => $anneeScolaire,
        ]);

        // Lier les notes à ce bulletin
        Note::where('eleve_id', $eleve->id)
            ->where('periode', $periode)
            ->whereNull('bulletin_id')
            ->update(['bulletin_id' => $bulletin->id]);

        // Calcul moyenne générale
        $notes = $bulletin->notes()->with('matiere')->get();

        $total = 0;
        $totalCoeff = 0;
        $notesParMatiere = [];

        foreach ($notes as $note) {
            $coefficient = optional($note->matiere)->pivot->coefficient ?? 1;
            $notesParMatiere[$note->matiere_id]['somme'] = ($notesParMatiere[$note->matiere_id]['somme'] ?? 0) + $note->valeur;
            $notesParMatiere[$note->matiere_id]['count'] = ($notesParMatiere[$note->matiere_id]['count'] ?? 0) + 1;
            $notesParMatiere[$note->matiere_id]['coefficient'] = $coefficient;
        }

        foreach ($notesParMatiere as $matiereId => $data) {
            $moyenneMatiere = $data['somme'] / $data['count'];
            $total += $moyenneMatiere * $data['coefficient'];
            $totalCoeff += $data['coefficient'];
        }

        $moyenne = $totalCoeff > 0 ? round($total / $totalCoeff, 2) : 0;

        // Mention + appréciation
        $mention = $this->getMention($moyenne);
        $appreciation = $this->getAppreciation($moyenne);

        // Générer PDF
        $pdf = Pdf::loadView('pdf.bulletin', compact('eleve', 'bulletin', 'notes', 'moyenne', 'mention', 'appreciation'));
        $path = 'bulletins/' . $eleve->id . '_' . $periode . '.pdf';
        Storage::put('public/' . $path, $pdf->output());

        $bulletin->update([
            'moyenne_generale' => $moyenne,
            'mention' => $mention,
            'appreciation' => $appreciation,
            'pdf_path' => $path,
            'date_edition' => now(),
        ]);

        return $bulletin->fresh('notes');
    }

    private function getMention($moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }

    private function getAppreciation($moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent travail.',
            $moyenne >= 14 => 'Bon travail, continuez ainsi.',
            $moyenne >= 12 => 'Efforts appréciés, peut mieux faire.',
            $moyenne >= 10 => 'Résultats moyens, doit redoubler d’efforts.',
            default => 'Travail insuffisant, sérieuse remise en question nécessaire.',
        };
    }
}


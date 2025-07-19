<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Eleve;
use App\Models\Note;
use App\Models\Classe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class BulletinService
{
    /**
     * Générer un bulletin pour un élève
     */
    public function genererBulletin(int $eleveId, string $periode, string $anneeScolaire = null): Bulletin
    {
        return DB::transaction(function () use ($eleveId, $periode, $anneeScolaire) {
            $eleve = Eleve::with(['user', 'classe.matieres'])->findOrFail($eleveId);
            
            if (!$anneeScolaire) {
                $anneeScolaire = date('Y') . '-' . (date('Y') + 1);
            }

            // Vérifier si un bulletin existe déjà pour cette période
            $bulletinExistant = Bulletin::where('eleve_id', $eleveId)
                ->where('annee_scolaire', $anneeScolaire)
                ->where('periode', $periode)
                ->first();

            if ($bulletinExistant) {
                throw new Exception('Un bulletin existe déjà pour cette période.');
            }

            // Récupérer toutes les notes de l'élève pour la période
            $notes = Note::where('eleve_id', $eleveId)
                ->where('periode', $periode)
                ->with(['matiere'])
                ->get();

            if ($notes->isEmpty()) {
                throw new Exception('Aucune note trouvée pour cette période.');
            }

            // Calculer les moyennes par matière
            $moyennesParMatiere = $this->calculerMoyennesParMatiere($notes);
            
            // Calculer la moyenne générale
            $moyenneGenerale = $this->calculerMoyenneGenerale($moyennesParMatiere, $eleve->classe);
            
            // Déterminer le rang
            $rang = $this->determinerRang($eleve->classe_id, $periode, $moyenneGenerale);
            
            // Déterminer la mention
            $mention = $this->determinerMention($moyenneGenerale);
            
            // Générer l'appréciation
            $appreciation = $this->genererAppreciation($moyenneGenerale, $moyennesParMatiere);

            // Créer le bulletin
            $bulletin = Bulletin::create([
                'eleve_id' => $eleveId,
                'classe_id' => $eleve->classe_id,
                'annee_scolaire' => $anneeScolaire,
                'periode' => $periode,
                'moyenne_generale' => $moyenneGenerale,
                'rang' => $rang,
                'mention' => $mention,
                'appreciation' => $appreciation,
                'date_edition' => now(),
            ]);

            // Générer le PDF du bulletin
            $pdfPath = $this->genererPDFBulletin($bulletin, $moyennesParMatiere);
            $bulletin->update(['pdf_path' => $pdfPath]);

            return $bulletin->load(['eleve.user', 'classe']);
        });
    }

    /**
     * Calculer les moyennes par matière
     */
    private function calculerMoyennesParMatiere($notes): array
    {
        $moyennes = [];
        
        foreach ($notes->groupBy('matiere_id') as $matiereId => $notesMatiere) {
            $moyenne = $notesMatiere->avg('valeur');
            $matiere = $notesMatiere->first()->matiere;
            
            $moyennes[$matiereId] = [
                'matiere' => $matiere,
                'moyenne' => round($moyenne, 2),
                'coefficient' => $matiere->pivot->coefficient ?? 1,
                'notes' => $notesMatiere
            ];
        }
        
        return $moyennes;
    }

    /**
     * Calculer la moyenne générale pondérée
     */
    private function calculerMoyenneGenerale(array $moyennesParMatiere, Classe $classe): float
    {
        $totalPoints = 0;
        $totalCoefficients = 0;
        
        foreach ($moyennesParMatiere as $moyenne) {
            $coefficient = $moyenne['coefficient'];
            $totalPoints += $moyenne['moyenne'] * $coefficient;
            $totalCoefficients += $coefficient;
        }
        
        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    /**
     * Déterminer le rang de l'élève dans sa classe
     */
    private function determinerRang(int $classeId, string $periode, float $moyenne): int
    {
        $elevesClasse = Eleve::where('classe_id', $classeId)->pluck('id');
        
        $rang = 1;
        foreach ($elevesClasse as $eleveId) {
            $notesEleve = Note::where('eleve_id', $eleveId)
                ->where('periode', $periode)
                ->with(['matiere'])
                ->get();
                
            if ($notesEleve->isNotEmpty()) {
                $moyennesParMatiere = $this->calculerMoyennesParMatiere($notesEleve);
                $moyenneEleve = $this->calculerMoyenneGenerale($moyennesParMatiere, Classe::find($classeId));
                
                if ($moyenneEleve > $moyenne) {
                    $rang++;
                }
            }
        }
        
        return $rang;
    }

    /**
     * Déterminer la mention selon la moyenne
     */
    private function determinerMention(float $moyenne): string
    {
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Générer une appréciation automatique
     */
    private function genererAppreciation(float $moyenne, array $moyennesParMatiere): string
    {
        $appreciations = [
            'excellent' => 'Excellent travail. Continuez ainsi !',
            'bon' => 'Bon travail. Quelques efforts supplémentaires pourraient améliorer vos résultats.',
            'moyen' => 'Travail moyen. Des efforts plus soutenus sont nécessaires.',
            'faible' => 'Résultats insuffisants. Un travail plus régulier est indispensable.'
        ];

        if ($moyenne >= 15) return $appreciations['excellent'];
        if ($moyenne >= 12) return $appreciations['bon'];
        if ($moyenne >= 10) return $appreciations['moyen'];
        return $appreciations['faible'];
    }

    /**
     * Générer le PDF du bulletin
     */
    private function genererPDFBulletin(Bulletin $bulletin, array $moyennesParMatiere): string
    {
        // Pour l'instant, on retourne un chemin fictif
        // En production, vous utiliseriez une bibliothèque comme DomPDF ou TCPDF
        $filename = 'bulletin_' . $bulletin->eleve_id . '_' . $bulletin->periode . '_' . date('Y-m-d') . '.pdf';
        return 'bulletins/' . $filename;
    }

    /**
     * Récupérer les bulletins d'un élève
     */
    public function getBulletinsEleve(int $eleveId): array
    {
        $bulletins = Bulletin::where('eleve_id', $eleveId)
            ->with(['eleve.user', 'classe'])
            ->orderBy('date_edition', 'desc')
            ->get();

        return $bulletins->toArray();
    }

    /**
     * Supprimer un bulletin
     */
    public function supprimerBulletin(int $bulletinId): bool
    {
        $bulletin = Bulletin::findOrFail($bulletinId);
        
        // Supprimer le fichier PDF si il existe
        if ($bulletin->pdf_path && Storage::exists($bulletin->pdf_path)) {
            Storage::delete($bulletin->pdf_path);
        }
        
        return $bulletin->delete();
    }
}

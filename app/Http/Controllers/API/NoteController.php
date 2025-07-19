<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Storage;


use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Models\Note;
use App\Services\NoteService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    protected NoteService $noteService;

    public function __construct(NoteService $noteService)
    {
        $this->noteService = $noteService;
    }

    /**
     * Saisir une note.
     */
    public function store(StoreNoteRequest $request)
    {
        $note = $this->noteService->saisirNote($request->validated());
        return response()->json(['message' => 'Note enregistrée avec succès', 'note' => $note]);
    }

    /**
     * Moyenne par matière d'un élève pour une période.
     */
    public function moyenneParMatiere(Request $request, $eleveId)
    {
        $request->validate([
            'periode' => 'required|string',
        ]);

        $moyennes = $this->noteService->calculerMoyennesParMatiere($eleveId, $request->periode);

        return response()->json(['moyennes' => $moyennes]);
    }

    /**
     * Moyenne générale d'un élève pour une période.
     */
    public function moyenneGenerale(Request $request, $eleveId)
    {
        $request->validate([
            'periode' => 'required|string',
        ]);

        $moyenne = $this->noteService->calculerMoyenneGenerale($eleveId, $request->periode);

        return response()->json(['moyenne_generale' => $moyenne]);
    }

    /**
     * Voir toutes les notes d'un élève pour une période.
     */
    public function notesEleve(Request $request, $eleveId)
    {
        $request->validate([
            'periode' => 'required|string',
        ]);

        $notes = Note::where('eleve_id', $eleveId)
            ->where('periode', $request->periode)
            ->with(['matiere', 'enseignant'])
            ->get();

        return response()->json(['notes' => $notes]);
    }
}

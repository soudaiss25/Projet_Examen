<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\BulletinService;

class BulletinController extends Controller
{
    protected BulletinService $service;

    public function __construct(BulletinService $service)
    {
        $this->service = $service;
    }

    public function generer(Request $request, int $eleveId)
    {
        $request->validate([
            'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
        ]);

        $eleve = Eleve::with('classe')->findOrFail($eleveId);

        $bulletin = $this->service->genererBulletin($eleve, $request->periode);

        return response()->json($bulletin);
    }

    public function telecharger(int $eleveId, string $periode)
    {
        $eleve = Eleve::findOrFail($eleveId);

        $bulletin = $eleve
            ->bulletins()
            ->where('periode', $periode)
            ->firstOrFail();

        // Vérifie l’existence du fichier dans le disque 'public'
        if (! $bulletin->pdf_path || ! Storage::disk('public')->exists($bulletin->pdf_path)) {
            abort(404, 'PDF non disponible');
        }

        return response()->file(
            storage_path("app/public/{$bulletin->pdf_path}")
        );
    }
}

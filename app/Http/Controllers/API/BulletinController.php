<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use Illuminate\Http\Request;
use App\Services\BulletinService;

class BulletinController extends Controller
{
    protected $service;

    public function __construct(BulletinService $service)
    {
        $this->service = $service;
    }

    public function generer(Request $request, $eleveId)
    {
        $request->validate([
            'periode' => 'required|in:trimestre_1,trimestre_2,trimestre_3,semestre_1,semestre_2',
        ]);

        $eleve = Eleve::with('classe')->findOrFail($eleveId);

        $bulletin = $this->service->genererBulletin($eleve, $request->periode);

        return response()->json($bulletin);
    }

    public function telecharger($eleveId, $periode)
    {
        $eleve = Eleve::findOrFail($eleveId);

        $bulletin = $eleve->bulletins()->where('periode', $periode)->firstOrFail();

        if (!$bulletin->pdf_path || !\Storage::exists('public/' . $bulletin->pdf_path)) {
            abort(404, 'PDF non disponible');
        }

        return response()->file(storage_path('app/public/' . $bulletin->pdf_path));
    }
}

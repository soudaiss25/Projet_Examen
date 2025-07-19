<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bulletin extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire',
        'periode',
        'moyenne_generale',
        'rang',
        'mention',
        'appreciation',
        'pdf_path',
        'date_edition'
    ];

    protected $casts = [
        'date_edition' => 'date',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    // Les notes ne sont pas directement liées au bulletin
    // Elles sont liées à l'élève et à la période
}

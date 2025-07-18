<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'date_absence',
        'periode',
        'motif',
        'est_justifiee',
        'document_justificatif',
        'commentaire'
    ];

    protected $casts = [
        'date_absence' => 'date',
        'est_justifiee' => 'boolean',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    // Scopes utiles
    public function scopeJustifiees($query)
    {
        return $query->where('est_justifiee', true);
    }

    public function scopeNonJustifiees($query)
    {
        return $query->where('est_justifiee', false);
    }

    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_absence', [$dateDebut, $dateFin]);
    }
}

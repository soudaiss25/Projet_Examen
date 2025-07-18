<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentEleve extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'type_document',
        'chemin_fichier',
        'date_depot',
        'est_valide'
    ];

    protected $casts = [
        'date_depot' => 'datetime',
        'est_valide' => 'boolean',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = [
        'niveau',
        'nom',
        'capacite',
        'annee_scolaire'
    ];

    public function eleve(): HasMany
    {
        return $this->hasMany(Eleve::class);
    }

    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(Matiere::class, 'classe_matiere')
            ->withPivot('coefficient');
    }

    public function enseignant(): BelongsToMany
    {
        return $this->belongsToMany(Enseignant::class, 'enseignant_classe');
    }

    public function bulletin(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }
}

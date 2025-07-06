<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnseignantMatiereClasse extends Model
{
    use HasFactory;
    protected $table = 'enseignant_matiere_classe';

    protected $fillable = [
        'enseignant_id',
        'matiere_id',
        'classe_id',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom',
        'coefficient',

    ];

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'enseignant_matiere_classe');
    }

    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'enseignant_matiere_classe');
    }

}

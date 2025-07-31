<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnseignantClasse extends Model
{
    use HasFactory;

    protected $table = 'enseignant_classe';

    protected $fillable = [
        'enseignant_id',
        'classe_id',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }
}

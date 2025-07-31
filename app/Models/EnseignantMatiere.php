<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnseignantMatiere extends Model
{
    use HasFactory;

    protected $table = 'enseignant_matiere';

    protected $fillable = [
        'enseignant_id',
        'matiere_id',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}

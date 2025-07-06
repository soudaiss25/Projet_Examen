<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $fillable = [
        'eleve_id',
        'nom',
        'fichier',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

}

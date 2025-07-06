<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bulletin extends Model
{
    use HasFactory;
    protected $fillable = [
        'eleve_id',
        'periode',
        'moyenne',
        'mention',
        'rang',
        'appreciation',
        'pdf_path',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

}

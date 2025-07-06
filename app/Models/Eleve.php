<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'classe_id',
        'matricule',
        'date_naissance',
        'adresse',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentUser::class, 'eleve_parent');
    }

}

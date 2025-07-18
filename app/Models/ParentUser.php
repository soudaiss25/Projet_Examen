<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profession',
        'nombre_enfants'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Eleve::class, 'parent_id');
    }
}

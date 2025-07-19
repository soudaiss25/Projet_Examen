<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'adresse',
        'telephone',
        'email',
        'password',
        'role',
        'image',
        'est_actif',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'est_actif' => 'boolean',
    ];

    public const ROLE_ADMIN      = 'admin';
    public const ROLE_ENSEIGNANT = 'enseignant';
    public const ROLE_PARENT     = 'parent';
    public const ROLE_ELEVE      = 'eleve';

    // Vérification de rôles
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEnseignant(): bool
    {
        return $this->role === self::ROLE_ENSEIGNANT;
    }

    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    public function isEleve(): bool
    {
        return $this->role === self::ROLE_ELEVE;
    }

    // Statut d'activation
    public function isActive(): bool
    {
        return $this->est_actif;
    }

    public function activer(): void
    {
        $this->est_actif = true;
        $this->save();
    }

    public function desactiver(): void
    {
        $this->est_actif = false;
        $this->save();
    }

    // Scopes utiles
    public function scopeActive($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeRoles($query, array $roles)
    {
        return $query->whereIn('role', $roles);
    }

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Relationships
    public function eleve(): HasOne
    {
        return $this->hasOne(Eleve::class);
    }

    public function enseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class);
    }

    public function parentUser(): HasOne
    {
        return $this->hasOne(ParentUser::class);
    }
}

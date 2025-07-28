<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nom' => 'Admin',
            'prenom' => 'Admin',
            'adresse' => 'Dakar, Sénégal',
            'telephone' => '771234567',
            'email' => 'admin@ecole.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'image' => null,
            'est_actif' => true,
        ]);
    }
}

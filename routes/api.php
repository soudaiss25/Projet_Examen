<?php

use App\Http\Controllers\API\BulletinController;


use App\Http\Controllers\API\ClasseController;
use App\Http\Controllers\API\EleveController;
use App\Http\Controllers\API\EnseignantController;
use App\Http\Controllers\API\NoteController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques (sans authentification)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées (authentification requise)
Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Route admin pour activer/désactiver un utilisateur
    Route::put('/users/{userId}/toggle-status', [AuthController::class, 'toggleUserStatus']);
});

Route::post('eleve', [UserController::class, 'createEleve']);
Route::post('enseignant', [UserController::class, 'createEnseignant']);
Route::post('parent', [UserController::class, 'createParent']);

Route::post('inscription', [EleveController::class, 'inscrire']);

Route::post('{enseignant}/matieres', [EnseignantController::class, 'affecterMatieres']);
Route::post('{enseignant}/classes', [EnseignantController::class, 'affecterClasses']);
Route::post('{enseignant}/affectation-automatique', [EnseignantController::class, 'affectationAutomatique']);
Route::post('{classe}/matieres', [ClasseController::class, 'affecterMatieres']);
Route::post('{classe}/affectation-automatique', [ClasseController::class, 'affectationAutomatique']);

Route::post('/', [NoteController::class, 'store']);
Route::get('/{eleveId}/moyennes/matiere', [NoteController::class, 'moyenneParMatiere']);
Route::get('/{eleveId}/moyenne-generale', [NoteController::class, 'moyenneGenerale']);
Route::get('/{eleveId}', [NoteController::class, 'notesEleve']);

Route::post('/{eleveId}/generer', [BulletinController::class, 'generer']);
Route::get('/{eleveId}/{periode}/telecharger', [BulletinController::class, 'telecharger']);

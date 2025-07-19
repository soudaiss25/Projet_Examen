<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EleveController;
use App\Http\Controllers\API\ClasseController;
use App\Http\Controllers\API\EnseignantController;
use App\Http\Controllers\API\MatiereController;
use App\Http\Controllers\API\NoteController;
use App\Http\Controllers\API\BulletinController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\AbsenceController;

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
    // Routes d'authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/users/{userId}/toggle-status', [AuthController::class, 'toggleUserStatus']);

    // Routes pour les élèves
    Route::apiResource('eleves', EleveController::class);
    
    // Routes pour les classes
    Route::apiResource('classes', ClasseController::class);
    
    // Routes pour les enseignants
    Route::apiResource('enseignants', EnseignantController::class);
    
    // Routes pour les matières
    Route::apiResource('matieres', MatiereController::class);
    
    // Routes pour les notes
    Route::apiResource('notes', NoteController::class);
    
    // Routes pour les bulletins
    Route::apiResource('bulletins', BulletinController::class);
    
    // Routes pour les documents
    Route::apiResource('documents', DocumentController::class);
    
    // Routes pour les absences
    Route::apiResource('absences', AbsenceController::class);

    // Routes spécifiques pour les notes
    Route::get('/eleves/{eleve}/notes', [NoteController::class, 'getEleveNotes']);
    Route::post('/eleves/{eleve}/notes', [NoteController::class, 'storeEleveNote']);
    
    // Routes spécifiques pour les bulletins
    Route::get('/eleves/{eleve}/bulletins', [BulletinController::class, 'getEleveBulletins']);
    Route::post('/eleves/{eleve}/bulletins', [BulletinController::class, 'generateBulletin']);
    
    // Routes spécifiques pour les documents
    Route::get('/eleves/{eleve}/documents', [DocumentController::class, 'getEleveDocuments']);
    Route::post('/eleves/{eleve}/documents', [DocumentController::class, 'uploadDocument']);
    
    // Routes spécifiques pour les absences
    Route::get('/eleves/{eleve}/absences', [AbsenceController::class, 'getEleveAbsences']);
});

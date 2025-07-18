<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained()->onDelete('cascade');
            $table->foreignId('classe_id')->constrained()->onDelete('cascade');
            $table->string('annee_scolaire');
            $table->enum('periode', ['trimestre_1', 'trimestre_2', 'trimestre_3', 'semestre_1', 'semestre_2']);
            $table->decimal('moyenne_generale', 4, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->string('mention')->nullable();
            $table->text('appreciation')->nullable();
            $table->string('pdf_path')->nullable();
            $table->date('date_edition')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};

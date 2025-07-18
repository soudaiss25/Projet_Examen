N <?php

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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained()->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained()->onDelete('cascade');
            $table->foreignId('enseignant_id')->constrained()->onDelete('cascade');
            $table->foreignId('bulletin_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('valeur', 4, 2);
            $table->enum('type_note', ['devoir', 'composition', 'interrogation', 'oral']);
            $table->enum('periode', ['trimestre_1', 'trimestre_2', 'trimestre_3', 'semestre_1', 'semestre_2']);
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};

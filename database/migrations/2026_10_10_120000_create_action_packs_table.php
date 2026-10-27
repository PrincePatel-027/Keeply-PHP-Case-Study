<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('pdf_document_id')->constrained('documents')->restrictOnDelete();
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('version');
            $table->json('facts_snapshot');
            $table->json('events_snapshot');
            $table->json('evidence_manifest');
            $table->text('letter_body');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['resolution_case_id', 'version']);
            $table->index(['workspace_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_packs');
    }
};

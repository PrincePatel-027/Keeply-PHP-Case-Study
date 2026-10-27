<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('version');
            $table->timestamp('assessed_at');
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(['resolution_case_id', 'version']);
            $table->index(['workspace_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_assessments');
    }
};

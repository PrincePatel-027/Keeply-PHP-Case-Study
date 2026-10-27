<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('problem_summary');
            $table->date('failure_date')->nullable();
            $table->string('desired_outcome', 30);
            $table->string('status', 30)->default('active');
            $table->string('priority', 20)->default('normal');
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_type', 30)->nullable();
            $table->unsignedBigInteger('resolution_value_minor')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_cases');
    }
};

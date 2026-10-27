<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('event_type', 30);
            $table->timestamp('occurred_at');
            $table->string('channel', 20)->nullable();
            $table->string('party_name')->nullable();
            $table->text('summary');
            $table->string('reference')->nullable();
            $table->timestamp('promised_follow_up_at')->nullable();
            $table->unsignedBigInteger('expense_minor')->nullable();
            $table->timestamps();

            $table->index(['resolution_case_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_events');
    }
};

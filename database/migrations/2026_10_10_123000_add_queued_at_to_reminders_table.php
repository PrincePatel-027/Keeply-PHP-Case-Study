<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable()->after('due_at');
            $table->index(['due_at', 'queued_at', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['due_at', 'queued_at', 'sent_at']);
            $table->dropColumn('queued_at');
        });
    }
};

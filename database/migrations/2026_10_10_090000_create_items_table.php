<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('purchase_price_minor')->nullable();
            $table->char('currency', 3);
            $table->string('seller_name')->nullable();
            $table->unsignedSmallInteger('return_days')->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });

        Schema::create('item_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_field_id')->constrained()->restrictOnDelete();
            $table->text('value_text')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 16, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'category_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_values');
        Schema::dropIfExists('items');
    }
};

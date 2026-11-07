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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->enum('meeting_type', ['online', 'in_person'])->default('online');
            $table->string('location_or_link', 500);
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'confirmed', 'reschedule_requested', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->boolean('confirmed_by_student')->default(false);
            $table->dateTime('confirmed_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};


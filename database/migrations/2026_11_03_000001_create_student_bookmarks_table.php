<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')
                  ->constrained('student_profiles')
                  ->cascadeOnDelete();
            $table->foreignId('internship_post_id')
                  ->constrained('internship_posts')
                  ->cascadeOnDelete();
            $table->timestamps();

            // One student can only bookmark a post once
            $table->unique(['student_profile_id', 'internship_post_id'], 'unique_student_bookmark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_bookmarks');
    }
};


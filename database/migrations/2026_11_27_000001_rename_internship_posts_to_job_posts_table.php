<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internship_posts') && !Schema::hasTable('job_posts')) {
            Schema::rename('internship_posts', 'job_posts');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_posts') && !Schema::hasTable('internship_posts')) {
            Schema::rename('job_posts', 'internship_posts');
        }
    }
};

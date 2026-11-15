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
        Schema::table('internship_posts', function (Blueprint $table) {
            $table->string('opportunity_type', 30)->default('internship')->index()->after('type');
            $table->string('experience_level', 30)->nullable()->index()->after('opportunity_type');
            $table->decimal('salary_max', 10, 2)->nullable()->after('stipend');
            $table->unsignedSmallInteger('duration_weeks')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internship_posts', function (Blueprint $table) {
            $table->dropIndex(['opportunity_type']);
            $table->dropIndex(['experience_level']);
            $table->dropColumn(['opportunity_type', 'experience_level', 'salary_max']);
            $table->unsignedSmallInteger('duration_weeks')->default(12)->change();
        });
    }
};


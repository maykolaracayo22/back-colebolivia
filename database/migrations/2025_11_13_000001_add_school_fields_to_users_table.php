<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->enum('role', ['admin','director','teacher','student','parent'])
                  ->default('student')
                  ->after('username');
            $table->boolean('must_change_password')->default(true)->after('role');
            $table->unsignedBigInteger('teacher_id')->nullable()->after('must_change_password');
            $table->unsignedBigInteger('student_id')->nullable()->after('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['student_id']);
            $table->dropColumn(['username','role','must_change_password','teacher_id','student_id']);
        });
    }
};

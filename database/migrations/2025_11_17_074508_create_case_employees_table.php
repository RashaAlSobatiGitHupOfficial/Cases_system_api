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

    Schema::create('case_employees', function (Blueprint $table) {
        $table->id();

        $table->foreignId('case_id')->constrained()->onDelete('cascade');
        $table->foreignId('employee_id')->constrained()->onDelete('cascade');

        $table->boolean('is_primary')->default(false);

        $table->enum('action', ['assigned','accepted','reassigned','removed' ])->nullable();

        // من قام بالإسناد أو التغيير
        $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

        $table->timestamp('started_at')->nullable();
        $table->timestamp('ended_at')->nullable();

        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_employees');
    }
};

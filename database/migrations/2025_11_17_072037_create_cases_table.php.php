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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('title');
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();
            $table->enum('type', ['technical', 'service_request', 'delay', 'miscommunication', 'enquery', 'others'])->default('enquery');
            $table->enum('way_entry', ['email', 'manual'])->default('email');
            $table->enum('status', ['opened', 'assigned', 'in_progress', 'reassigned', 'closed'])->default('opened');
            $table->timestamps();
        });
    }   

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};

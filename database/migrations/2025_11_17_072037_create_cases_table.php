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
            $table->string('title');
            $table->string('description');
            $table->text('attachment')->nullable();
            $table->text('note')->nullable();
            $table->enum('type', ['technical', 'service_request', 'delay', 'miscommunication', 'enquery', 'others'])->default('enquery');
            $table->enum('way_entry', ['email', 'manual'])->default('email');
            $table->enum('status', ['opened', 'assigned', 'in_progress', 'reassigned', 'closed'])->default('opened');
            $table->enum('priority', ['high', 'middle', 'low', 'normal'])->default('normal');
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

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
    Schema::create('inbound_emails', function (Blueprint $table) {
        $table->id();

        $table->string('provider')->default('gmail'); // future-proof
        $table->string('gmail_message_id')->unique();
        $table->string('gmail_thread_id')->nullable();

        $table->string('from_email')->nullable();
        $table->string('from_name')->nullable();
        $table->string('subject')->nullable();
        $table->timestamp('received_at')->nullable();

        $table->string('status')->default('new'); // new|processed|skipped|failed
        $table->unsignedBigInteger('case_id')->nullable();

        $table->json('raw_headers')->nullable();
        $table->timestamps();

        $table->index(['provider', 'status']);
        $table->index('received_at');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_emails');
    }
};

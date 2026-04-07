<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Activity Logs ─────────────────────────────────────────────────────
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Who
            $table->foreignId('user_id')
                  ->nullable()                                      // null = system action
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('role_name', 50)->nullable();            // snapshot of role at time of action

            // What
            $table->enum('action', [
                'created', 'updated', 'deleted',
                'viewed',  'exported', 'imported',
                'login',   'logout',   'failed_login',
            ]);
            $table->string('module', 50);                           // customers, transactions, reports…
            $table->unsignedBigInteger('record_id')->nullable();    // affected row id
            $table->string('record_label', 200)->nullable();        // human name e.g. "TIP TOP FURNITURE"

            // Before & After (JSON diff)
            $table->json('old_values')->nullable();                 // snapshot before change
            $table->json('new_values')->nullable();                 // snapshot after change

            // Description
            $table->string('description', 500)->nullable();         // plain text summary

            // Request info
            $table->string('ip_address', 45)->nullable();           // IPv4 / IPv6
            $table->string('user_agent', 300)->nullable();

            $table->timestamp('created_at')->useCurrent();          // no updated_at — logs are immutable

            // Indexes for log viewer
            $table->index('user_id');
            $table->index('module');
            $table->index('action');
            $table->index('record_id');
            $table->index('created_at');
        });

        // ── Login Logs ────────────────────────────────────────────────────────
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()                                      // null when login fails
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('email_attempted', 150);                 // what was typed
            $table->enum('status', ['success', 'failed', 'logout']);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('activity_logs');
    }
};

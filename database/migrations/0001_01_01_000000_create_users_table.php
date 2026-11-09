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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('telefono')->unique();
            $table->string('direccion');
            $table->string('password')->nullable();
            $table->string('avatar')->nullable();
            $table->string('idioma', 10)->default('es');
            $table->boolean('activo')->default(true);
            $table->string('origen', 30)->default('registro'); // enum OrigenUsuario
            $table->timestamp('email_verified_at')->nullable();
            $table->string('invitacion_token', 100)->nullable();
            $table->timestamp('invitacion_enviada_at')->nullable();
            $table->timestamp('invitacion_aceptada_at')->nullable();
            $table->timestamp('politica_aceptada_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

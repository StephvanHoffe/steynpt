<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leden en beheer (Steyn). Tijden staan in UTC; de site toont ze in Europe/Amsterdam.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('goal')->nullable();
            $table->string('plan')->nullable();
            // geen, aangevraagd, actief, gepauzeerd of gestopt
            $table->string('coaching_status', 20)->default('geen');
            $table->text('coach_note')->nullable();
            $table->string('referral_code', 20)->unique();
            $table->uuid('referred_by_id')->nullable()->index();
            $table->string('role', 10)->default('member');
            $table->boolean('marketing_opt_in')->default(false);
            // Vriendenactie: moment waarop Steyn de korting voor de uitnodiger heeft verrekend.
            $table->dateTime('referral_reward_at')->nullable();
            // Wachtwoord moet om de 8 weken vernieuwd worden. Leeg = sinds het aanmaken van het account.
            $table->dateTime('password_changed_at')->nullable();
            // Tweestapsverificatie met een authenticator-app: geheim (versleuteld), moment van instellen, laatst gebruikte stap.
            $table->text('totp_secret')->nullable();
            $table->dateTime('totp_enabled_at')->nullable();
            $table->unsignedBigInteger('totp_last_step')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });

        // Sessies van Laravel (inloggen). user_id is de uuid van het lid.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

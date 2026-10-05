<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alle tabellen van SteynPT (gelijk aan de Next.js-versie). Tijden in UTC, dagen als "YYYY-MM-DD" in Europe/Amsterdam.
return new class extends Migration
{
    public function up(): void
    {
        // Eenmalige herstelcodes voor als de telefoon met de app kwijt is (alleen de hash wordt bewaard).
        Schema::create('recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('week', 10);
            $table->double('weight')->nullable();
            $table->unsignedTinyInteger('energy');
            $table->unsignedTinyInteger('sleep');
            $table->unsignedTinyInteger('nutrition');
            $table->unsignedTinyInteger('workouts');
            $table->text('note')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->unique(['user_id', 'week']);
        });

        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('interest', 40);
            $table->text('message')->nullable();
            $table->boolean('handled')->default(false);
            $table->dateTime('created_at')->nullable();
        });

        // Intake van de klant: één per lid, de inhoud is gecontroleerd met App\Support\Intake.
        Schema::create('intakes', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('data');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // genereren -> concept (of fout) -> gepland -> gepubliceerd; oude versies worden "vervangen".
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('status', 15)->default('genereren');
            // Huidige (door Steyn bewerkte) inhoud en het oorspronkelijke AI-concept, als JSON.
            $table->longText('content')->nullable();
            $table->longText('ai_draft')->nullable();
            $table->string('source', 10)->default('ai');
            $table->text('instruction')->nullable();
            $table->text('error')->nullable();
            $table->string('model')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('published_at')->nullable();
            // Dag waarop het schema voor de klant ingaat; leeg = direct bij publiceren.
            $table->string('starts_on', 10)->nullable();
            // Dag waarop de klant toe is aan een nieuw schema.
            $table->string('renew_on', 10)->nullable();
            $table->index(['user_id', 'type', 'status']);
        });

        // Metingen die Steyn invoert; de klant ziet ze als voortgang in Mijn omgeving.
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('measured_at');
            foreach (['weight', 'body_fat', 'muscle_mass', 'waist', 'hip', 'chest', 'arm', 'thigh'] as $column) {
                $table->double($column)->nullable();
            }
            $table->text('note')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index(['user_id', 'measured_at']);
        });

        // Agenda: wekelijkse beschikbaarheid van Steyn per locatie (tijden in Europe/Amsterdam).
        Schema::create('availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // 1 = maandag ... 7 = zondag
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('location', 20);
        });

        // Periodes waarin niet geboekt kan worden (vakantie, ziekte, privé).
        Schema::create('blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('location', 20);
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('status', 15)->default('gepland');
            $table->text('note')->nullable();
            $table->string('cancelled_by', 10)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        // Eenvoudige sleutel/waarde-instellingen, zoals het geheime token van de iCal-feed.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value');
        });

        // Teksten van de website die Steyn in het beheer heeft aangepast. Sleutel: pagina.onderdeel.veld, waarde als JSON.
        // Staat een tekst hier niet in, dan toont de site de standaardtekst uit de code (app/Content).
        Schema::create('site_texts', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->longText('value');
            $table->foreignUuid('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['site_texts', 'settings', 'appointments', 'blocked_periods', 'availability', 'measurements', 'plans', 'intakes', 'contact_requests', 'check_ins', 'recovery_codes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

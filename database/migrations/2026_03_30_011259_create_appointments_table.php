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
        Schema::create('appointments', function (Blueprint $table) {
            
            $table->id();

            $table->foreignId('teacher_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('student_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->dateTime('lesson_time');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
/*
    Schema::create('appointments', function (Blueprint $table) { ... });

    Új tábla létrehozása appointments néven.
    A function belsejében definiálod az oszlopokat.
    $table->id();
    Létrehoz egy elsődleges kulcsot (primary key), tipikusan bigint unsigned auto increment.
    Ez lesz a rekord egyedi azonosítója (id).
    $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
    Létrehoz egy teacher_id oszlopot.
    Ez idegen kulcs lesz a users tábla id oszlopára.
    constrained('users') miatt Laravel automatikusan foreign key kapcsolatot tesz rá.
    onDelete('cascade') azt jelenti:
    Ha a kapcsolt user törlődik, az ő appointment rekordjai is automatikusan törlődnek.
    $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
    Ugyanez, csak a diákra.
    student_id szintén users.id-re mutat.
    Törléskor itt is kaszkád törlés történik.
    $table->dateTime('lesson_time');
    Az óra időpontját tárolod.
    Pontos dátum + idő.
    $table->timestamps();
    Két oszlopot ad automatikusan:
    created_at
    updated_at
    Laravel ezeket automatikusan kezeli mentéskor.
    Mit jelent ez adatmodell szinten?
    Egy appointment rekordnak van egy tanára (teacher_id) és egy diákja (student_id).
    Mindkettő a users táblára hivatkozik.
    Egy user lehet több appointmentben is tanárként vagy diákként.
    Gyakorlati következmények:
    Nem marad árva appointment, ha usert törölsz, mert a cascade takarít.
    Vigyázni kell, mert user törléskor sok appointment is elveszhet.
    Ha ezt nem szeretnéd, cascade helyett lehet restrict vagy set null stratégia.
    A Blueprint Laravel migrációban a tábla tervrajza:
    A Schema::create vagy Schema::table callback-jében a $table változó típusa Blueprint.
    Ezen hívod a metódusokat, amikkel oszlopokat, indexeket, foreign key-eket definiálsz.
    Laravel ebből generálja az SQL-t az adott adatbázishoz.
*/
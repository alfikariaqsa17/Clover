<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_plants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('nickname');
            $t->string('photo')->nullable();
            $t->string('location')->nullable();
            $t->date('acquired_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('care_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_plant_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);                                     // siram | pupuk | repot | pangkas | bersihkan daun | lainnya
            $t->unsignedSmallInteger('every_days');
            $t->date('next_due_date')->index();
            $t->date('last_done_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('care_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_plant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('care_schedule_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 30);
            $t->string('note', 500)->nullable();
            $t->date('done_at');
            $t->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);
            $t->string('title');
            $t->string('body', 500)->nullable();
            $t->string('url')->nullable();
            $t->dateTime('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        foreach (['user_notifications', 'care_logs', 'care_schedules', 'user_plants'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id(); // Уникальный ID модуля
            $table->unsignedBigInteger('subject_id'); // ID предмета, к которому относится модуль
            $table->string('name'); // Название модуля
            $table->integer('totalLessonCount'); // Общее количество уроков
            $table->integer('completedLessonCount')->default(0); // Завершенное количество уроков
            $table->date('startDate'); // Дата начала модуля
            $table->json('lessonDays'); // Дни недели для уроков
            $table->time('startTime'); // Время начала урока
            $table->string('duration'); // Продолжительность урока
            $table->string('grade')->default('not_set');
            $table->string('status')->default('unpaid');
            $table->timestamps();

            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
}

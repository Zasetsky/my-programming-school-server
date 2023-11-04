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
            $table->integer('total_lesson_count'); // Общее количество уроков
            $table->integer('completed_lesson_count')->default(0); // Завершенное количество уроков
            $table->date('start_date'); // Дата начала модуля
            $table->date('end_date')->nullable(); // Дата конца модуля
            $table->json('lesson_days'); // Дни недели для уроков
            $table->time('start_time'); // Время начала урока
            $table->string('duration'); // Продолжительность урока
            $table->string('grade')->default('not_set');
            $table->string('status')->default('unpaid');
            $table->text('comment')->nullable();
            $table->date('next_lesson_date')->nullable();
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

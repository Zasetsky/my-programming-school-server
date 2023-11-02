<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonsTable extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id(); // Уникальный ID урока
            $table->unsignedBigInteger('module_id'); // ID модуля, к которому относится урок
            $table->string('subject_name'); // Имя предмета
            $table->date('lesson_date'); // Дата урока
            $table->time('start_time'); // Время начала урока
            $table->time('end_time'); // Время окончания урока
            $table->text('homework')->nullable(); // Домашнее задание
            $table->string('status')->default('not_active'); // Статус урока
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
}

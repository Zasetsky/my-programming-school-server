<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ModuleController;

// Маршрут для получения информации о текущем авторизованном пользователе
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршруты для аутентификации и регистрации
// Они доступны всем пользователям, даже неавторизованным, поэтому middleware здесь не используется
Route::post('/register', [AuthController::class, 'register']); // Регистрация пользователя
Route::post('/login', [AuthController::class, 'login']); // Вход пользователя
Route::post('/logout', [AuthController::class, 'logout']) // Выход пользователя
    ->middleware('auth:api'); // Middleware здесь используется, так как только авторизованные пользователи могут выходить

// Группа маршрутов с middleware для авторизации
Route::middleware('auth:api')->group(function () {
    // Роуты для предметов
    Route::get('/getSubjects', [SubjectController::class, 'getSubjects']);
    Route::post('/addSubject', [SubjectController::class, 'addSubject']);
    Route::put('/updateSubject/{id}', [SubjectController::class, 'updateSubject']);

    // Роуты для модулей
    Route::post('/addModule/{subjectId}', [ModuleController::class, 'addModule']);
    Route::put('/updateModule/{moduleId}', [ModuleController::class, 'updateModule']);
    Route::get('/getModulesForSubject/{subjectId}', [ModuleController::class, 'getModulesForSubject']);

    // Роуты для уроков
    Route::get('/getAllLessonsForUser', [LessonController::class, 'getAllLessonsForUser']);
    Route::post('/setHomework/{lessonId}', [LessonController::class, 'setHomework']);
    Route::post('/rescheduleLesson/{lessonId}', [LessonController::class, 'rescheduleLesson']);
});
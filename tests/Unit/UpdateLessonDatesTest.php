<?php

namespace Tests\Unit\Console\Commands;
use Mockery;
use App\Console\Commands\UpdateLessonDates;
use App\Models\Subject;
use App\Models\User;
use App\Services\LessonDateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateLessonDatesTest extends TestCase
{
    use RefreshDatabase;

    protected $mockLessonDateService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockLessonDateService = Mockery::mock(LessonDateService::class);
        $this->app->instance(LessonDateService::class, $this->mockLessonDateService);
    }

    public function testHandle()
    {
        // Создание тестового пользователя
        $user = User::factory()->create();
    
        // Использование ID пользователя при создании объекта Subject
        $subject = Subject::factory([
            'user_id' => $user->id
        ])->create();
    
        // Остальной код теста
        $this->mockLessonDateService->shouldReceive('calculateDates')->andReturnUsing(function ($module, $completedLessonCount) {
            $module['nextLessonDate'] = 'new-date';
            return $module;
        });
    
        $subject->modules = [
            [
                'nextLessonDate' => '24-09-2023',
                'startTime' => '10:00',
                'duration' => '1 hour 30 minutes',
                'completedLessonCount' => 0
            ]
        ];
        $subject->save();
    
        Artisan::call(UpdateLessonDates::class);
    
        $updatedSubject = Subject::find($subject->id);
        $this->assertEquals('new-date', $updatedSubject->modules[0]['nextLessonDate']);
    }    
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\LessonDateService;

use Illuminate\Support\Facades\Log;


class SubjectController extends Controller
{

    protected $lessonDateService;

    public function __construct(LessonDateService $lessonDateService)
    {
        $this->lessonDateService = $lessonDateService;
    }

    public function addSubject(Request $request)
    {
        try {
            Log::info('addSubject called', ['request' => $request->all()]);

            Log::info('Auth ID', ['id' => auth()->id()]);

            $request->validate([
                'subject_code' => [
                    'required',
                    'string',
                    Rule::unique('subjects')->where(function ($query) {
                        return $query->where('user_id', auth()->id());
                    }),
                ],
                'name' => 'required|string',
            ]);

            $subject = new Subject();
            $subject->subject_code = $request->subject_code;
            $subject->user_id = Auth::id(); // Получаем ID текущего пользователя
            $subject->name = $request->name;

            Log::info('Before save', ['subject' => json_encode($subject)]);
            $subject->save();

            Log::info('Subject added successfully', ['subject' => $subject]);

            return response()->json(['message' => 'Subject added successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error in addSubject', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function updateSubject(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        $subject->name = $request->name;

        $subject->save();

        return response()->json(['message' => 'Subject updated successfully'], 200);
    }

    public function getSubjects()
    {
        // Извлечение всех предметов
        $subjects = Subject::all()->makeHidden(['user_id']);

        return response()->json(['subjects' => $subjects], 200);
    }
}
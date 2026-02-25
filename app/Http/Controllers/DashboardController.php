<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\TaskStatus;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = now();
        $last7Days = $now->copy()->subDays(7);
        $activityStart = $now->copy()->subDays(13)->startOfDay();

        $tasks = $user->tasks()
            ->with('steps:id,idea_id,completed')
            ->get();

        $totalTasks = $tasks->count();
        $pendingTasks = $tasks->where('status', TaskStatus::PENDING)->count();
        $inProgressTasks = $tasks->where('status', TaskStatus::IN_PROGRESS)->count();
        $completedTasks = $tasks->where('status', TaskStatus::COMPLETED)->count();

        $completionRate = $totalTasks > 0
            ? (int) round(($completedTasks / $totalTasks) * 100)
            : 0;

        $tasksCreatedLast7Days = $tasks
            ->filter(fn (Task $task) => $task->created_at !== null && $task->created_at->gte($last7Days))
            ->count();

        $tasksCompletedLast7Days = $tasks
            ->filter(fn (Task $task) => $task->status === TaskStatus::COMPLETED && $task->updated_at !== null && $task->updated_at->gte($last7Days))
            ->count();

        $totalSteps = $tasks->sum(fn (Task $task) => $task->steps->count());
        $completedSteps = $tasks->sum(fn (Task $task) => $task->steps->where('completed', true)->count());
        $stepCompletionRate = $totalSteps > 0
            ? (int) round(($completedSteps / $totalSteps) * 100)
            : 0;

        $topTags = $tasks
            ->pluck('tags')
            ->flatten(1)
            ->map(fn ($tag) => ltrim(strtolower(trim((string) $tag)), '#'))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(6);

        $activity = collect(range(0, 13))
            ->map(function (int $offset) use ($activityStart, $tasks) {
                $day = $activityStart->copy()->addDays($offset);

                $created = $tasks->filter(
                    fn (Task $task) => $task->created_at !== null && $task->created_at->isSameDay($day)
                )->count();

                $completed = $tasks->filter(
                    fn (Task $task) => $task->status === TaskStatus::COMPLETED && $task->updated_at !== null && $task->updated_at->isSameDay($day)
                )->count();

                return [
                    'label' => $day->format('d M'),
                    'created' => $created,
                    'completed' => $completed,
                ];
            });

        $activityMax = max(1, (int) $activity->max(fn (array $day) => max($day['created'], $day['completed'])));

        return view('dashboard.index', [
            'totalTasks' => $totalTasks,
            'pendingTasks' => $pendingTasks,
            'inProgressTasks' => $inProgressTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'tasksCreatedLast7Days' => $tasksCreatedLast7Days,
            'tasksCompletedLast7Days' => $tasksCompletedLast7Days,
            'totalSteps' => $totalSteps,
            'completedSteps' => $completedSteps,
            'stepCompletionRate' => $stepCompletionRate,
            'topTags' => $topTags,
            'activity' => $activity,
            'activityMax' => $activityMax,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\CreateTask;
use App\Actions\UpdateTask;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $view = $request->query('view') === 'board' || $request->query('view') === 'bord' ? 'board' : 'list';
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
        $selectedTag = ltrim(strtolower(trim((string) $request->query('tag', ''))), '#');
        $hasTagsColumn = Schema::hasColumn('ideas', 'tags');

        $tasks = $user
            ->tasks()
            ->when($view !== 'board' && in_array($request->status, TaskStatus::values()), fn ($query) => $query->where('status', $request->status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->get();

        if ($hasTagsColumn && $selectedTag !== '') {
            $tasks = $tasks
                ->filter(function ($task) use ($selectedTag) {
                    return collect($task->tags ?? [])
                        ->contains(fn ($tag) => ltrim(strtolower(trim((string) $tag)), '#') === $selectedTag);
                })
                ->values();
        }

        $availableTags = $hasTagsColumn
            ? $user
                ->tasks()
                ->pluck('tags')
                ->flatten(1)
                ->map(fn ($tag) => ltrim(strtolower(trim((string) $tag)), '#'))
                ->filter()
                ->unique()
                ->sort()
                ->values()
            : collect();

        return view('task.index', [
            'tasks' => $tasks,
            'selectedView' => $view,
            'statusCounts' => Task::statusCounts($user),
            'availableTags' => $availableTags,
        ]);
    }

    public function store(TaskRequest $request, CreateTask $action)
    {
        $action->handle($request->validated());

        return to_route('task.index')->with('succes', 'Taak aangemaakt!');
    }

    public function show(Task $task)
    {
        Gate::authorize('workWith', $task);

        return view('task.show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task)
    {
        Gate::authorize('workWith', $task);
    }

    public function update(TaskRequest $request, Task $task, UpdateTask $action)
    {
        Gate::authorize('workWith', $task);

        $action->handle($request->safe()->all(), $task);

        return back()->with('Succes', 'Taak is aangepast');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('workWith', $task);

        $task->delete();

        return to_route('task.index');
    }

    public function updateStatus(Request $request, Task $task)
    {
        Gate::authorize('workWith', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        if ($validated['status'] === TaskStatus::COMPLETED->value) {
            $task->steps()->update(['completed' => true]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Status bijgewerkt.');
    }
}

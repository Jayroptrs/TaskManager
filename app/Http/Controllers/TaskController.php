<?php

namespace App\Http\Controllers;

use App\Actions\CreateTask;
use App\Actions\UpdateTask;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\User;
use App\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $view = $request->query('view') === 'board' || $request->query('view') === 'bord' ? 'board' : 'list';
        $search = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['newest', 'oldest', 'deadline_soon', 'deadline_late'], true)
            ? $request->query('sort')
            : 'newest';
        $selectedTag = ltrim(strtolower(trim((string) $request->query('tag', ''))), '#');
        $work = in_array($request->query('work'), ['solo', 'team'], true) ? $request->query('work') : 'all';
        $due = in_array($request->query('due'), ['all', 'upcoming', 'overdue', 'none'], true)
            ? $request->query('due')
            : 'all';
        $hasTagsColumn = Schema::hasColumn('ideas', 'tags');
        $baseTasksQuery = Task::query()->visibleTo($user);
        $today = now()->startOfDay()->toDateString();
        $upcomingEnd = now()->startOfDay()->addDays(7)->toDateString();

        if ($work === 'solo') {
            $baseTasksQuery
                ->where('user_id', $user->id)
                ->whereDoesntHave('collaborators');
        }

        if ($work === 'team') {
            $baseTasksQuery->where(function ($query) use ($user) {
                $query
                    ->where(function ($ownerQuery) use ($user) {
                        $ownerQuery
                            ->where('user_id', $user->id)
                            ->whereHas('collaborators');
                    })
                    ->orWhereHas('collaborators', fn ($collabQuery) => $collabQuery->whereKey($user->id));
            });
        }

        $tasksQuery = (clone $baseTasksQuery)
            ->when($view !== 'board' && in_array($request->status, TaskStatus::values()), fn ($query) => $query->where('status', $request->status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($due === 'upcoming', fn ($query) => $query
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$today, $upcomingEnd]))
            ->when($due === 'overdue', fn ($query) => $query
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today))
            ->when($due === 'none', fn ($query) => $query->whereNull('due_date'));

        if ($sort === 'oldest') {
            $tasksQuery->oldest();
        } elseif ($sort === 'deadline_soon') {
            $tasksQuery
                ->orderByRaw('case when due_date is null then 1 else 0 end')
                ->orderBy('due_date')
                ->latest('created_at');
        } elseif ($sort === 'deadline_late') {
            $tasksQuery
                ->orderByRaw('case when due_date is null then 1 else 0 end')
                ->orderByDesc('due_date')
                ->latest('created_at');
        } else {
            $tasksQuery->latest();
        }

        $tasks = $tasksQuery->get();

        if ($hasTagsColumn && $selectedTag !== '') {
            $tasks = $tasks
                ->filter(function ($task) use ($selectedTag) {
                    return collect($task->tags ?? [])
                        ->contains(fn ($tag) => ltrim(strtolower(trim((string) $tag)), '#') === $selectedTag);
                })
                ->values();
        }

        $availableTags = $hasTagsColumn
            ? (clone $baseTasksQuery)
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
            'selectedWork' => $work,
            'selectedDue' => $due,
            'selectedSort' => $sort,
            'selectedTag' => $selectedTag,
            'statusCounts' => Task::statusCounts($user),
            'availableTags' => $availableTags,
        ]);
    }

    public function store(TaskRequest $request, CreateTask $action)
    {
        $validated = $request->validated();
        if ($this->wouldExceedOpenTaskLimit($request->user(), null, $validated['status'])) {
            return back()
                ->withErrors(['status' => __('messages.open_tasks_limit_reached', ['max' => $this->openTaskLimit()])])
                ->withInput();
        }

        $task = $action->handle($validated);
        $task->recordActivity('task_created', $request->user()->id);
        $this->addCollaboratorsByEmail($task, (string) $request->input('invite_emails'), $request->user()->id);
        $task->syncDueDateReminders($request->user()->id);

        return to_route('task.index')->with('success', 'Taak aangemaakt!');
    }

    public function show(Request $request, Task $task)
    {
        $isAdminViewer = $request->user()?->isAdmin() && ! $task->user->is($request->user());
        if (! $isAdminViewer) {
            Gate::authorize('workWith', $task);
        }

        $task->load([
            'user:id,name,email',
            'collaborators:id,name,email',
            'steps.assignee:id,name',
            'comments.user:id,name,avatar_path',
            'comments.attachments',
        ]);

        return view('task.show', [
            'task' => $task,
            'isOwner' => $task->user->is($request->user()),
            'canManageCollaborators' => $task->user->is($request->user()),
            'canEditTask' => $task->user->is($request->user()),
            'canToggleSteps' => ! $isAdminViewer,
            'canCommentOnTask' => ! $isAdminViewer,
            'activeInvite' => $task->invites()->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->latest()->first(),
            'activityLogs' => $task->user->is($request->user())
                ? $task->activityLogs()->with('actor:id,name')->latest()->limit(40)->get()
                : collect(),
        ]);
    }

    public function edit(Task $task)
    {
        Gate::authorize('manageTask', $task);
    }

    public function update(TaskRequest $request, Task $task, UpdateTask $action)
    {
        Gate::authorize('manageTask', $task);
        $validated = $request->safe()->all();

        if ($this->wouldExceedOpenTaskLimit($task->user, $task, (string) ($validated['status'] ?? $task->status->value))) {
            return back()
                ->withErrors(['status' => __('messages.open_tasks_limit_reached', ['max' => $this->openTaskLimit()])])
                ->withInput();
        }

        $action->handle($validated, $task);
        $task->recordActivity('task_updated', $request->user()->id);
        $task->syncDueDateReminders($request->user()->id);
        if ($task->user->is($request->user())) {
            $this->addCollaboratorsByEmail($task, (string) $request->input('invite_emails'), $request->user()->id);
        }

        return back()->with('Succes', 'Taak is aangepast');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('manageTask', $task);

        $task->delete();

        return to_route('task.index');
    }

    public function updateStatus(Request $request, Task $task)
    {
        Gate::authorize('workWith', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        if ($this->wouldExceedOpenTaskLimit($task->user, $task, $validated['status'])) {
            $message = __('messages.open_tasks_limit_reached', ['max' => $this->openTaskLimit()]);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->withErrors(['status' => $message]);
        }

        $previousStatus = $task->status->value;

        $task->update([
            'status' => $validated['status'],
        ]);
        $task->recordActivity('status_changed', $request->user()->id, [
            'from' => $previousStatus,
            'to' => $validated['status'],
        ]);

        if ($validated['status'] === TaskStatus::COMPLETED->value) {
            $task->steps()->update(['completed' => true]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', __('messages.task_status_updated'));
    }

    private function wouldExceedOpenTaskLimit(User $owner, ?Task $task, string $newStatus): bool
    {
        if (! $this->isOpenStatus($newStatus)) {
            return false;
        }

        $currentlyOpen = $task ? $this->isOpenStatus($task->status->value) : false;
        if ($currentlyOpen) {
            return false;
        }

        $openTasksCount = $owner->tasks()
            ->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value])
            ->count();

        return $openTasksCount >= $this->openTaskLimit();
    }

    private function isOpenStatus(string $status): bool
    {
        return in_array($status, [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value], true);
    }

    private function openTaskLimit(): int
    {
        return max(1, (int) config('tasks.max_open_tasks', 50));
    }

    private function addCollaboratorsByEmail(Task $task, string $rawEmails, int $addedBy): void
    {
        if (trim($rawEmails) === '') {
            return;
        }

        $emails = collect(preg_split('/[\s,;]+/', strtolower($rawEmails), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        $users = User::query()
            ->whereIn('email', $emails->all())
            ->get(['id', 'name', 'email']);

        $users
            ->reject(fn (User $user) => $user->id === $task->user_id)
            ->reject(fn (User $user) => $task->collaborators()->whereKey($user->id)->exists())
            ->each(function (User $user) use ($task, $addedBy) {
                $existsPending = TaskCollaborationRequest::query()
                    ->where('task_id', $task->id)
                    ->where('invitee_id', $user->id)
                    ->where('status', TaskCollaborationRequest::STATUS_PENDING)
                    ->exists();

                if (! $existsPending) {
                    TaskCollaborationRequest::create([
                        'task_id' => $task->id,
                        'inviter_id' => $addedBy,
                        'invitee_id' => $user->id,
                        'status' => TaskCollaborationRequest::STATUS_PENDING,
                    ]);
                    $task->recordActivity('collab_request_sent', $addedBy, [
                        'invitee' => $user->name ?? $user->email,
                    ]);
                }
            });
    }
}

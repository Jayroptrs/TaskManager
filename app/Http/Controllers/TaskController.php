<?php

namespace App\Http\Controllers;

use Carbon\CarbonInterface;
use App\Actions\CreateTask;
use App\Actions\UpdateTask;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\TaskCollaborationRequest;
use App\Models\User;
use App\TaskStatus;
use Illuminate\Support\Carbon;
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
        return $this->renderIndex($request, 'active', false);
    }

    public function archived(Request $request)
    {
        return $this->renderIndex($request, 'archived', true);
    }

    private function renderIndex(Request $request, string $archive, bool $archivePage)
    {
        $user = Auth::user();
        $view = match ((string) $request->query('view', 'list')) {
            'board', 'bord' => 'board',
            'calendar', 'kalender' => 'calendar',
            default => 'list',
        };
        $search = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['newest', 'oldest', 'deadline_soon', 'deadline_late', 'priority_high'], true)
            ? $request->query('sort')
            : 'newest';
        $selectedTag = ltrim(strtolower(trim((string) $request->query('tag', ''))), '#');
        $work = in_array($request->query('work'), ['solo', 'team'], true) ? $request->query('work') : 'all';
        $due = in_array($request->query('due'), ['all', 'upcoming', 'overdue', 'none'], true)
            ? $request->query('due')
            : 'all';
        $status = in_array($request->query('status'), TaskStatus::values(), true)
            ? $request->query('status')
            : null;

        if ($archivePage || ($archive !== 'active' && $view !== 'list')) {
            $view = 'list';
        }

        $taskIndexRouteName = $archivePage ? 'task.archived' : 'task.index';
        $filterSessionKey = $archivePage ? 'tasks.archived_last_filter' : 'tasks.last_filter';

        if ($request->boolean('save_last_filter')) {
            $lastFilter = array_filter([
                'q' => $search !== '' ? $search : null,
                'sort' => $sort !== 'newest' ? $sort : null,
                'tag' => $selectedTag !== '' ? $selectedTag : null,
                'work' => $work !== 'all' ? $work : null,
                'due' => $due !== 'all' ? $due : null,
                'status' => $view !== 'board' ? $status : null,
                'view' => ! $archivePage && $view !== 'list' ? $view : null,
                'month' => $view === 'calendar' ? (string) $request->query('month', '') : null,
            ], fn ($value) => $value !== null && $value !== '');

            $request->session()->put($filterSessionKey, $lastFilter);
        }

        $lastFilter = $request->session()->get($filterSessionKey, []);
        $lastFilterUrl = is_array($lastFilter) && $lastFilter !== []
            ? route($taskIndexRouteName, $lastFilter)
            : null;

        $hasTagsColumn = Schema::hasColumn('ideas', 'tags');
        $baseTasksQuery = Task::query()
            ->select(['id', 'user_id', 'title', 'description', 'status', 'priority', 'due_date', 'tags', 'image_path', 'created_at', 'updated_at', 'archived_at'])
            ->visibleTo($user)
            ->applyArchiveState($archive);
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
            ->when($view !== 'board' && $status !== null, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($hasTagsColumn && $selectedTag !== '', fn ($query) => $query->whereRaw('LOWER(tags) LIKE ?', ['%"'.$selectedTag.'"%']))
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
        } elseif ($sort === 'priority_high') {
            $tasksQuery
                ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 when 'low' then 2 else 3 end")
                ->latest('created_at');
        } else {
            $tasksQuery->latest();
        }

        $tasks = $tasksQuery->get();

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

        $calendarMonth = null;
        $calendarDays = collect();
        $tasksByDueDate = collect();
        $calendarMonthTaskCount = 0;

        if ($view === 'calendar') {
            $calendarMonth = $this->resolveCalendarMonth((string) $request->query('month', ''));
            $calendarGridStart = $calendarMonth->copy()->startOfWeek(CarbonInterface::MONDAY);
            $calendarGridEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);

            for ($cursor = $calendarGridStart->copy(); $cursor->lte($calendarGridEnd); $cursor->addDay()) {
                $calendarDays->push($cursor->copy());
            }

            $tasksByDueDate = $tasks
                ->filter(fn (Task $task) => $task->due_date !== null)
                ->groupBy(fn (Task $task) => $task->due_date->toDateString());

            $calendarMonthTaskCount = $tasks
                ->filter(fn (Task $task) => $task->due_date?->isSameMonth($calendarMonth))
                ->count();
        }

        return view('task.index', [
            'tasks' => $tasks,
            'selectedView' => $view,
            'selectedWork' => $work,
            'selectedDue' => $due,
            'selectedSort' => $sort,
            'selectedTag' => $selectedTag,
            'statusCounts' => Task::statusCounts($user, $archive),
            'availableTags' => $availableTags,
            'calendarMonth' => $calendarMonth,
            'calendarDays' => $calendarDays,
            'tasksByDueDate' => $tasksByDueDate,
            'calendarMonthTaskCount' => $calendarMonthTaskCount,
            'lastFilterUrl' => $lastFilterUrl,
            'isArchivePage' => $archivePage,
            'taskIndexRouteName' => $taskIndexRouteName,
            'pageTitle' => $archivePage ? __('task.archived_page_title') : __('task.page_title'),
            'pageSubtitle' => $archivePage ? __('task.archived_page_subtitle') : __('task.page_subtitle'),
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
        Gate::authorize('workWith', $task);

        $canWorkWith = Gate::allows('workWith', $task);
        $canManageTask = Gate::allows('manageTask', $task);
        $canManageCollaborators = Gate::allows('manageCollaborators', $task);

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
            'canManageCollaborators' => $canManageCollaborators,
            'canEditTask' => $canManageTask,
            'canToggleSteps' => $canWorkWith,
            'canCommentOnTask' => $canWorkWith,
            'activeInvite' => $task->invites()->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->latest()->first(),
            'activityLogs' => ($task->user->is($request->user()) || $request->user()?->isAdmin())
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
        $before = $task->activitySnapshot();

        if ($this->wouldExceedOpenTaskLimit($task->user, $task, (string) ($validated['status'] ?? $task->status->value))) {
            return back()
                ->withErrors(['status' => __('messages.open_tasks_limit_reached', ['max' => $this->openTaskLimit()])])
                ->withInput();
        }

        $action->handle($validated, $task);
        $task->refresh();
        $changes = $task->activityChangesFrom($before);
        if ($changes !== []) {
            $task->recordActivity('task_updated', $request->user()->id, [
                'changes' => $changes,
            ]);
        }
        $task->syncDueDateReminders($request->user()->id);
        if ($task->user->is($request->user())) {
            $this->addCollaboratorsByEmail($task, (string) $request->input('invite_emails'), $request->user()->id);
        }

        return back()->with('Succes', 'Taak is aangepast');
    }

    public function archive(Request $request, Task $task)
    {
        Gate::authorize('manageTask', $task);

        if (! $task->isArchived()) {
            $task->forceFill(['archived_at' => now()])->save();
            $task->dueDateReminders()->delete();
            $task->recordActivity('task_archived', $request->user()->id);
        }

        return to_route('task.index')->with('success', __('messages.task_archived'));
    }

    public function restore(Request $request, Task $task)
    {
        Gate::authorize('manageTask', $task);

        if ($task->isArchived()) {
            $task->forceFill(['archived_at' => null])->save();
            $task->recordActivity('task_restored', $request->user()->id);
            $task->syncDueDateReminders($request->user()->id);
        }

        return back()->with('success', __('messages.task_restored'));
    }

    public function destroy(Task $task)
    {
        Gate::authorize('manageTask', $task);

        $task->recordActivity('task_deleted', auth()->id(), [
            'task_title' => $task->title,
        ]);

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
            ->notArchived()
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

    private function resolveCalendarMonth(string $month): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}

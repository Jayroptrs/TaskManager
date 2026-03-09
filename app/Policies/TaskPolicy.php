<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function workWith(User $user, Task $task): bool
    {
        if ($task->user->is($user)) {
            return true;
        }

        return $task->collaborators()->whereKey($user->id)->exists();
    }

    public function manageCollaborators(User $user, Task $task): bool
    {
        return $task->user->is($user);
    }

    public function manageTask(User $user, Task $task): bool
    {
        return $task->user->is($user);
    }
}

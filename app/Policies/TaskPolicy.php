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
        return $task->user->is($user);
    }
}

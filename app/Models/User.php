<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Task;
use App\Models\SupportMessage;
use App\Models\TaskComment;
use App\Models\TaskCommentMention;
use App\Models\TaskCollaborationRequest;
use App\Models\Step;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (! empty($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->taskComments()
                ->whereNull('parent_comment_id')
                ->get()
                ->each
                ->delete();

            $user->tasks()->get()->each->delete();
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function collaborativeTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'idea_collaborators', 'user_id', 'idea_id')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    public function supportMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function incomingCollaborationRequests(): HasMany
    {
        return $this->hasMany(TaskCollaborationRequest::class, 'invitee_id');
    }

    public function outgoingCollaborationRequests(): HasMany
    {
        return $this->hasMany(TaskCollaborationRequest::class, 'inviter_id');
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function taskCommentMentions(): HasMany
    {
        return $this->hasMany(TaskCommentMention::class, 'mentioned_user_id');
    }

    public function unreadTaskCommentMentions(): HasMany
    {
        return $this->taskCommentMentions()->whereNull('read_at');
    }

    public function taskDueDateReminders(): HasMany
    {
        return $this->hasMany(TaskDueDateReminder::class, 'user_id');
    }

    public function unreadTaskDueDateReminders(): HasMany
    {
        return $this->dueTaskDueDateReminders()->whereNull('read_at');
    }

    public function dueTaskDueDateReminders(): HasMany
    {
        return $this->taskDueDateReminders()
            ->whereDate('remind_on_date', '<=', today());
    }

    public function assignedSteps(): HasMany
    {
        return $this->hasMany(Step::class, 'assigned_user_id');
    }

    public function isAdmin(): bool
    {
        return (bool) ($this->attributes['is_admin'] ?? false);
    }

    public function avatarUrl(): string
    {
        if (! empty($this->avatar_path)) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return asset('images/avatar-anonymous.svg');
    }
}

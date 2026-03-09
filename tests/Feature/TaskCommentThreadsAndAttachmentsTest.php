<?php

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('collaborator can reply in thread to an existing task comment', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $parentComment = TaskComment::query()->create([
        'idea_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Hoofdreactie',
    ]);

    $this->actingAs($collaborator)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Dit is een thread-reactie',
            'parent_comment_id' => $parentComment->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('task_comments', [
        'idea_id' => $task->id,
        'user_id' => $collaborator->id,
        'body' => 'Dit is een thread-reactie',
        'parent_comment_id' => $parentComment->id,
    ]);
});

test('authorized user can download task comment attachments while outsider is forbidden', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $outsider = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Comment met bijlage',
            'attachments' => [
                UploadedFile::fake()->create('spec.pdf', 200, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $comment = TaskComment::query()->latest('id')->firstOrFail();
    $attachment = TaskCommentAttachment::query()->where('task_comment_id', $comment->id)->firstOrFail();

    Storage::disk('public')->assertExists($attachment->path);

    $this->actingAs($collaborator)
        ->get(route('task.comments.attachments.download', [$task, $comment, $attachment]))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($outsider)
        ->get(route('task.comments.attachments.download', [$task, $comment, $attachment]))
        ->assertForbidden();
});

test('deleting comment also deletes uploaded attachment files', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Comment met bijlage om te verwijderen',
            'attachments' => [
                UploadedFile::fake()->create('to-delete.txt', 20, 'text/plain'),
            ],
        ])
        ->assertRedirect();

    $comment = TaskComment::query()->latest('id')->firstOrFail();
    $attachment = TaskCommentAttachment::query()->where('task_comment_id', $comment->id)->firstOrFail();
    $path = $attachment->path;

    Storage::disk('public')->assertExists($path);

    $this->actingAs($owner)
        ->delete(route('task.comments.destroy', [$task, $comment]))
        ->assertRedirect();

    $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    $this->assertDatabaseMissing('task_comment_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($path);
});

test('reply cannot be attached to a parent comment from another task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $otherTask = Task::factory()->create(['user_id' => $owner->id]);
    $foreignParent = TaskComment::query()->create([
        'idea_id' => $otherTask->id,
        'user_id' => $owner->id,
        'body' => 'Andere taak parent',
    ]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Onjuiste thread parent',
            'parent_comment_id' => $foreignParent->id,
        ])
        ->assertSessionHasErrors('parent_comment_id');

    $this->assertDatabaseMissing('task_comments', [
        'idea_id' => $task->id,
        'body' => 'Onjuiste thread parent',
    ]);
});

test('guest cannot post task comments', function () {
    $task = Task::factory()->create();

    $this->post(route('task.comments.store', $task), [
        'comment' => 'Gastreactie',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('task_comments', [
        'idea_id' => $task->id,
        'body' => 'Gastreactie',
    ]);
});

test('comment rejects more than five attachments', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $attachments = [
        UploadedFile::fake()->create('a1.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('a2.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('a3.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('a4.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('a5.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('a6.pdf', 10, 'application/pdf'),
    ];

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Te veel bijlagen',
            'attachments' => $attachments,
        ])
        ->assertSessionHasErrors('attachments');

    $this->assertDatabaseMissing('task_comments', [
        'idea_id' => $task->id,
        'body' => 'Te veel bijlagen',
    ]);
});

test('comment rejects unsupported attachment mime type', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('task.comments.store', $task), [
            'comment' => 'Bestandstype test',
            'attachments' => [
                UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
            ],
        ])
        ->assertSessionHasErrors('attachments.0');

    $this->assertDatabaseMissing('task_comments', [
        'idea_id' => $task->id,
        'body' => 'Bestandstype test',
    ]);
});

test('attachment download returns 404 when attachment does not belong to comment', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $commentA = TaskComment::query()->create([
        'idea_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Comment A',
    ]);

    $commentB = TaskComment::query()->create([
        'idea_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Comment B',
    ]);

    $attachmentB = $commentB->attachments()->create([
        'path' => 'comment-attachments/file-b.pdf',
        'original_name' => 'file-b.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);
    Storage::disk('public')->put($attachmentB->path, 'x');

    $this->actingAs($owner)
        ->get(route('task.comments.attachments.download', [$task, $commentA, $attachmentB]))
        ->assertNotFound();
});

test('attachment download returns 404 when comment does not belong to task', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $taskA = Task::factory()->create(['user_id' => $owner->id]);
    $taskB = Task::factory()->create(['user_id' => $owner->id]);

    $commentA = TaskComment::query()->create([
        'idea_id' => $taskA->id,
        'user_id' => $owner->id,
        'body' => 'Comment A',
    ]);

    $attachment = $commentA->attachments()->create([
        'path' => 'comment-attachments/file-a.pdf',
        'original_name' => 'file-a.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);
    Storage::disk('public')->put($attachment->path, 'x');

    $this->actingAs($owner)
        ->get(route('task.comments.attachments.download', [$taskB, $commentA, $attachment]))
        ->assertNotFound();
});

test('deleting parent comment removes replies and their attachment files', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $parent = TaskComment::query()->create([
        'idea_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Parent',
    ]);

    $reply = TaskComment::query()->create([
        'idea_id' => $task->id,
        'user_id' => $owner->id,
        'body' => 'Reply',
        'parent_comment_id' => $parent->id,
    ]);

    $attachment = $reply->attachments()->create([
        'path' => 'comment-attachments/reply-file.txt',
        'original_name' => 'reply-file.txt',
        'mime_type' => 'text/plain',
        'size' => 42,
    ]);
    Storage::disk('public')->put($attachment->path, 'reply');

    $this->actingAs($owner)
        ->delete(route('task.comments.destroy', [$task, $parent]))
        ->assertRedirect();

    $this->assertDatabaseMissing('task_comments', ['id' => $parent->id]);
    $this->assertDatabaseMissing('task_comments', ['id' => $reply->id]);
    $this->assertDatabaseMissing('task_comment_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});

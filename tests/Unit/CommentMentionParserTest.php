<?php

use App\Models\Task;
use App\Models\User;
use App\Support\CommentMentionParser;

test('parser resolves email mention for collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Ping @collab@example.com', $task, $owner);

    expect($mentioned->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser resolves bracket name mention for collaborator', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['name' => 'John Smith']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Kun jij kijken @[John Smith]?', $task, $owner);

    expect($mentioned->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser supports dot underscore and hyphen name variants', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['name' => 'Jane Doe']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $parser = app(CommentMentionParser::class);

    $dotMention = $parser->mentionedUsers('Ping @jane.doe', $task, $owner);
    $underscoreMention = $parser->mentionedUsers('Ping @jane_doe', $task, $owner);
    $hyphenMention = $parser->mentionedUsers('Ping @jane-doe', $task, $owner);

    expect($dotMention->pluck('id')->all())->toBe([$collaborator->id]);
    expect($underscoreMention->pluck('id')->all())->toBe([$collaborator->id]);
    expect($hyphenMention->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser handles accented names via ascii normalization', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['name' => 'Jörg Álvarez']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Kun jij dit doen @[Jorg Alvarez]?', $task, $owner);

    expect($mentioned->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser ignores mentions for users outside the task', function () {
    $owner = User::factory()->create();
    User::factory()->create(['email' => 'outsider@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Ping @outsider@example.com', $task, $owner);

    expect($mentioned)->toHaveCount(0);
});

test('parser ignores self mentions by actor', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Ik tag mezelf @owner@example.com', $task, $owner);

    expect($mentioned)->toHaveCount(0);
});

test('parser deduplicates repeated mentions for same user', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $body = 'Ping @john@example.com en @[John Smith] en @john.smith';
    $mentioned = app(CommentMentionParser::class)->mentionedUsers($body, $task, $owner);

    expect($mentioned)->toHaveCount(1);
    expect($mentioned->first()->id)->toBe($collaborator->id);
});

test('parser does not parse inline email without mention prefix', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Mail staat hier collab@example.com zonder @mention.', $task, $owner);

    expect($mentioned)->toHaveCount(0);
});

test('parser does not parse at-sign in middle of word as mention', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('abc@collab@example.com', $task, $owner);

    expect($mentioned)->toHaveCount(0);
});

test('parser accepts mention preceded by opening parenthesis', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('(@collab@example.com) kun jij dit oppakken?', $task, $owner);

    expect($mentioned->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser handles punctuation after mention token', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create(['email' => 'collab@example.com']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($collaborator->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Kun je dit doen @collab@example.com, alvast bedankt.', $task, $owner);

    expect($mentioned->pluck('id')->all())->toBe([$collaborator->id]);
});

test('parser returns only participants when duplicate names exist inside and outside task', function () {
    $owner = User::factory()->create();
    $inTaskUser = User::factory()->create(['name' => 'Alex Johnson']);
    $outsideUser = User::factory()->create(['name' => 'Alex Johnson']);
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $task->collaborators()->attach($inTaskUser->id, ['added_by' => $owner->id]);

    $mentioned = app(CommentMentionParser::class)
        ->mentionedUsers('Check @[Alex Johnson]', $task, $owner);

    expect($mentioned)->toHaveCount(1);
    expect($mentioned->first()->id)->toBe($inTaskUser->id);
    expect($mentioned->contains(fn (User $user) => $user->id === $outsideUser->id))->toBeFalse();
});

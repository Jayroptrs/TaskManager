<?php

namespace App\Http\Requests;

use App\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use App\TaskStatus;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::in(TaskPriority::values())],
            'due_date' => ['nullable', 'date'],
            'reminders_enabled' => ['nullable', 'boolean'],
            'reminder_days' => ['nullable', 'array', 'min:1'],
            'reminder_days.*' => ['integer', 'min:0', 'max:365'],
            'links' => ['nullable', 'array'],
            'links.*' => ['url', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'steps' => ['nullable', 'array'],
            'steps.*.description' => ['string', 'max:255'],
            'steps.*.completed' => ['boolean'],
            'steps.*.assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'invite_emails' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $links = $this->input('links');
        if (! is_array($links)) {
            return;
        }

        $normalizedLinks = collect($links)
            ->map(fn ($link) => $this->normalizeLinkValue($link))
            ->all();

        $this->merge([
            'links' => $normalizedLinks,
        ]);
    }

    private function normalizeLinkValue(mixed $value): string
    {
        $link = trim((string) $value);
        if ($link === '') {
            return $link;
        }

        $hasScheme = preg_match('~^[a-z][a-z0-9+\-.]*://~i', $link) === 1;
        if (! $hasScheme && str_starts_with(strtolower($link), 'www.')) {
            return 'https://'.$link;
        }

        return $link;
    }
}

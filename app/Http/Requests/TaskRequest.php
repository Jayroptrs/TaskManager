<?php

namespace App\Http\Requests;

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
            'invite_emails' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

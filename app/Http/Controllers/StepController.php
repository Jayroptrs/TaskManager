<?php

namespace App\Http\Controllers;

use App\Models\Step;
use Illuminate\Support\Facades\Gate;

class StepController extends Controller
{
    public function update(Step $step)
    {
        Gate::authorize('workWith', $step->task);

        $completed = ! $step->completed;
        $step->update(['completed' => $completed]);
        $step->task->recordActivity('step_toggled', auth()->id(), [
            'step' => $step->description,
            'completed' => $completed,
        ]);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::query()->count();
        $totalTasks = Task::query()->count();
        $completedTasks = Task::query()->where('status', 'completed')->count();
        $openSupportCount = SupportMessage::query()->where('status', 'open')->count();
        $resolvedSupportCount = SupportMessage::query()->where('status', 'resolved')->count();

        $recentUsers = User::query()
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'email', 'created_at']);

        $supportMessages = SupportMessage::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(15);

        return view('admin.index', [
            'totalUsers' => $totalUsers,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'openSupportCount' => $openSupportCount,
            'resolvedSupportCount' => $resolvedSupportCount,
            'recentUsers' => $recentUsers,
            'supportMessages' => $supportMessages,
        ]);
    }

    public function resolve(SupportMessage $supportMessage)
    {
        if ($supportMessage->status !== 'resolved') {
            $supportMessage->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
        }

        return back()->with('success', 'Supportbericht gemarkeerd als afgehandeld.');
    }

    public function destroyUser(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser && $currentUser->is($user)) {
            return back()->withErrors(['admin' => 'Je kunt je eigen account niet verwijderen vanuit het adminpaneel.']);
        }

        if ($user->isAdmin()) {
            return back()->withErrors(['admin' => 'Admin accounts kunnen niet via dit paneel verwijderd worden.']);
        }

        $user->delete();

        return back()->with('success', 'Gebruiker is verwijderd.');
    }
}

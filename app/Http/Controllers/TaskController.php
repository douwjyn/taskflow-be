<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        $teams = Team::with(['leader', 'members', 'tasks.users'])
            ->where('leader_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->get();

        $tasks = $teams->flatMap(function ($team) {
            return $team->tasks;
        });


        return response()->json([
            'tasks' => $tasks,
            'teams' => $teams
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'team_id' => 'required|exists:teams,id',
        ]);
        // $user = User::findOrfail($request->user_id);

        // Create the task
        $task = Task::create([
            'title' => $request->title,
            'due_date' => $request->due_date,
            'team_id' => $request->team_id,
        ]);
        // Assign the user to the task
        $task->users()->attach($request->user_id);
        $team = Team::findOrFail($request->team_id);
        return response()->json([
            'message' => 'Task assigned successfully.', 
            'team_id' => $task->team_id,
            'team_name' => $task->team->name,
            'team' => $team->load('leader', 'members.tasks', 'tasks.users')
            ]
            , 201);
    }

    public function pending_tasks(User $user) {
        $pending_tasks = Task::with(['users', 'team']) // eager load
            ->where('status', 'Pending')
            ->whereHas('team', function ($q) use ($user) {
                $q->where('leader_id', $user->id) // user is leader
                ->orWhereHas('members', fn($q2) => $q2->where('users.id', $user->id)); // user is member
            })
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date,
                    'team' => $task->team?->name ?? 'Unknown Team',
                    'assignee' => $task->users->first()?->name ?? 'Unassigned',
                    'status' => $task->status,
                ];
            });

        return response()->json(['pending_tasks' => $pending_tasks]);
    }

     public function complete_tasks(User $user) {
        $completed_tasks = Task::with(['users', 'team']) // eager load
            ->where('status', 'Completed')
            ->whereHas('team', function ($q) use ($user) {
                $q->where('leader_id', $user->id) // user is leader
                ->orWhereHas('members', fn($q2) => $q2->where('users.id', $user->id)); // user is member
            })
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date,
                    'team' => $task->team?->name ?? 'Unknown Team',
                    'assignee' => $task->users->first()?->name ?? 'Unassigned',
                    'status' => $task->status,
                ];
            });

        return response()->json(['completed_tasks' => $completed_tasks]);
    }

    public function all(Task $task)
    {
        return response()->json(['tasks' => $task->get()]);

    }

    public function delete_all()
    {
        Task::truncate();
        return response()->json(['message' => 'deleted all']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $request->validate([
            'user_name' => 'required|exists:users,name',
            'team_id' => 'required|exists:teams,id'
        ]); 
        if ($task->status == 'Completed') {
            $task->update([
                'status' => 'Pending'
            ]);
            
            return response()->json(['message' => 'Task status updated to Incomplete.'], 200);
        }

        $task->update([
            'status' => 'Completed',
            'due_date' => null
        ]);

        $assignee = $task->users->first();

        \App\Models\Notification::create([
            'team_id' => $request->team_id,
            'type' => 'complete',
            'message' => $assignee->name . " completed the task for " . $task->title
        ]);

        return response()->json(['message' => 'Task updated successfully.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        
    }
}

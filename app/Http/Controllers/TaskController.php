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

        // Create the task
        $task = Task::create([
            'title' => $request->title,
            'due_date' => $request->due_date,
            'team_id' => $request->team_id,
        ]);

        // Assign the user to the task
        $task->users()->attach($request->user_id);

        return response()->json(['message' => 'Task assigned successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        //
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
        
        if ($task->status == 'Completed') {
            $task->update([
                'status' => 'Pending'
            ]);
            return response()->json(['message' => 'Task status updated to Incomplete.'], 200);
        }

        $task->update([
            'status' => 'Completed',
        ]);


        return response()->json(['message' => 'Task updated successfully.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        //
    }
}

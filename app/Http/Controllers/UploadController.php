<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
            'team_id' => 'required|exists:teams,id',

            'submission_file' => 'nullable|file|mimes:ppt,pptx,xls,xlsx,pdf,doc,docx|max:2048',
            'submission_link' => [
                'nullable',
                'url',
                'regex:/^https?:\/\/(docs\.google\.com)\/.+$/'
            ],
        ], [
            'submission_link.regex' => 'The submission link must be a valid Google Docs URL.',
        ]);

        $task = Task::findOrFail($request->input('task_id'));
        $team = Team::findOrFail($task->team_id);
        $type = '';
        if (!$request->hasFile('submission_file') && !$request->filled('submission_link')) {
            return response()->json([
                'message' => 'Please upload either a file or a Google Docs Link.',
                'response' => 'error'
            ], 500);
            // return back()->withErrors(['submission' => 'You must provide either a file or a Google Docs link.'])->withInput();
        }

        if ($request->hasFile('submission_file')) {
             $task->update([
                'submission' => $request->file('submission_file')->store('uploads', 'public'),
                'submitted_date' => Carbon::now(),
                // 'status' => 'Completed',
            ]);
            $type = "file";

        } else if ($request->has('submission_link')) {
            $task->update([
                'submission' => $request->input('submission_link'),
                'submitted_date' => Carbon::now(),
                // 'status' => 'Completed',
            ]);
            $type = "link";

        }

      
        $user = User::findOrFail($request->input('user_id'));
        // activity()
        //     ->performedOn($team)
        //     ->causedBy($user)
        //     ->withProperties(['subject_id' => $task->id])
        //     ->log($user->name . ' uploaded a file');

        activity()
            ->performedOn($team)
            ->causedBy($user)
            ->withProperties(['role' => $request->input('role', 'member')])
            ->log($task->title . ": " . $user->name . " uploaded a " . $type);

        Notification::create([
            "team_id" => $team->id,
            "type" => "upload",
            "message" => $user->name . " uploaded a " . $type . " for " . $task->title
        ]);

        // $upload->user_id = $request->user()->id;
        // $upload->task_id = $request->input('task_id');
        // $upload->team_id = $request->input('team_id');
        // $upload->file_path = $request->file('file')->store('uploads', 'public');
        // $upload->slug = str_slug($request->file('file')->getClientOriginalName(), '-');
        // $upload->save();

        return response()->json([
            'message' => 'File uploaded successfully.',
            'task' => $task,
            'response' => 'success'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function download(Task $task)
    {
        $file = $task->submission;
        if (!$file) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Get file size (in bytes)
        $size = Storage::disk('public')->exists($file) ? Storage::disk('public')->size($file) : null;

        if ($size === null) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('public')->download($file);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Upload $upload)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Upload $upload)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $file = $task->submission;
        if ($file && Storage::disk('public')->exists($file)) {
            Storage::disk('public')->delete($file);
        }   

        $task->update([
            'submission' => null,
            'submitted_date' => null,
            // 'status' => 'Pending',
        ]);

        return response()->json(['message' => 'File deleted successfully.', 'task' => $task], 200);
    }
}

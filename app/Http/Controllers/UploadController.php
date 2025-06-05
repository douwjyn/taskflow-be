<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use App\Models\Task;
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
            'submission' => 'required|file|mimes:ppt,pptx,xls,xlsx,pdf,doc,docx|max:2048',
        ]);

        $task = Task::findOrFail($request->input('task_id'));
        $task->update([
            'submission' => $request->file('submission')->store('uploads', 'public'),
            'submitted_date' => Carbon::now(),
            // 'status' => 'Completed',
        ]);

        // $upload->user_id = $request->user()->id;
        // $upload->task_id = $request->input('task_id');
        // $upload->team_id = $request->input('team_id');
        // $upload->file_path = $request->file('file')->store('uploads', 'public');
        // $upload->slug = str_slug($request->file('file')->getClientOriginalName(), '-');
        // $upload->save();

        return response()->json(['message' => 'File uploaded successfully.', 'task' => $task], 201);
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
    public function destroy(Upload $upload)
    {
        //
    }
}

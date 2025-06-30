<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FigureSubmission;
use Illuminate\Http\Request;

class FigureSubmissionController extends Controller
{
    /**
     * List pending figure submissions.
     */
    public function index()
    {
        $submissions = FigureSubmission::where('status', 'pending')
            ->latest()
            ->paginate(10);

        return view('admin.figure-submissions.index', compact('submissions'));
    }

    /**
     * Display the specified resource.
     */
    public function show(FigureSubmission $figureSubmission)
    {
        return view('admin.figure-submissions.show', [
            'submission' => $figureSubmission,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FigureSubmission $figureSubmission)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $figureSubmission->update($validated);

        return redirect()
            ->route('figure-submissions.index')
            ->with('status', 'Submission updated.');
    }

}

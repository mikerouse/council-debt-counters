<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhistleblowerReport;
use Illuminate\Http\Request;

class WhistleblowerReportController extends Controller
{
    /**
     * List pending whistleblower reports.
     */
    public function index()
    {
        $reports = WhistleblowerReport::where('status', 'pending')
            ->latest()
            ->paginate(10);

        return view('admin.whistleblower-reports.index', compact('reports'));
    }

    /**
     * Display the specified resource.
     */
    public function show(WhistleblowerReport $whistleblowerReport)
    {
        return view('admin.whistleblower-reports.show', [
            'report' => $whistleblowerReport,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WhistleblowerReport $whistleblowerReport)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $whistleblowerReport->update($validated);

        return redirect()
            ->route('whistleblower-reports.index')
            ->with('status', 'Report updated.');
    }

}

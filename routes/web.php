<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/figure-submissions', [\App\Http\Controllers\Admin\FigureSubmissionController::class, 'index'])->name('figure-submissions.index');
    Route::get('/figure-submissions/{submission}', [\App\Http\Controllers\Admin\FigureSubmissionController::class, 'show'])->name('figure-submissions.show');
    Route::patch('/figure-submissions/{submission}', [\App\Http\Controllers\Admin\FigureSubmissionController::class, 'update'])->name('figure-submissions.update');

    Route::get('/whistleblower-reports', [\App\Http\Controllers\Admin\WhistleblowerReportController::class, 'index'])->name('whistleblower-reports.index');
    Route::get('/whistleblower-reports/{report}', [\App\Http\Controllers\Admin\WhistleblowerReportController::class, 'show'])->name('whistleblower-reports.show');
    Route::patch('/whistleblower-reports/{report}', [\App\Http\Controllers\Admin\WhistleblowerReportController::class, 'update'])->name('whistleblower-reports.update');
});

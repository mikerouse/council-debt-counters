@extends('layouts.admin')

@section('title', 'Review Figure Submission')

@section('content')
<h1>Review Figure Submission</h1>
<dl class="row">
    <dt class="col-sm-3">Council</dt>
    <dd class="col-sm-9">{{ optional($submission->council)->name ?? 'Unknown' }}</dd>
    <dt class="col-sm-3">Financial Year</dt>
    <dd class="col-sm-9">{{ $submission->financial_year }}</dd>
</dl>
<form method="post" action="{{ route('figure-submissions.update', $submission) }}">
    @csrf
    @method('patch')
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select" aria-describedby="statusHelp">
            <option value="pending" @selected($submission->status === 'pending')>Pending</option>
            <option value="approved" @selected($submission->status === 'approved')>Approve</option>
            <option value="rejected" @selected($submission->status === 'rejected')>Reject</option>
        </select>
        <div id="statusHelp" class="form-text">Selecting approve will update the figures automatically.</div>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection

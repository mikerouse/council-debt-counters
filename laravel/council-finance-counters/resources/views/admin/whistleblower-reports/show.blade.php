@extends('layouts.admin')

@section('title', 'Review Whistleblower Report')

@section('content')
<h1>Review Whistleblower Report</h1>
<dl class="row">
    <dt class="col-sm-3">Council</dt>
    <dd class="col-sm-9">{{ optional($report->council)->name ?? 'Unknown' }}</dd>
    <dt class="col-sm-3">Email</dt>
    <dd class="col-sm-9">{{ $report->contact_email }}</dd>
    <dt class="col-sm-3">Description</dt>
    <dd class="col-sm-9">{{ $report->description }}</dd>
    @if($report->attachment_path)
        <dt class="col-sm-3">Attachment</dt>
        <dd class="col-sm-9"><a href="{{ asset('storage/'.$report->attachment_path) }}">Download</a></dd>
    @endif
</dl>
<form method="post" action="{{ route('whistleblower-reports.update', $report) }}">
    @csrf
    @method('patch')
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select">
            <option value="pending" @selected($report->status === 'pending')>Pending</option>
            <option value="approved" @selected($report->status === 'approved')>Approve</option>
            <option value="rejected" @selected($report->status === 'rejected')>Reject</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection

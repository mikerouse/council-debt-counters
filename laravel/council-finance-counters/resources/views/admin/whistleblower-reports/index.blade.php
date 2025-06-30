@extends('layouts.admin')

@section('title', 'Whistleblower Reports')

@section('content')
<h1>Whistleblower Reports</h1>
@if($reports->isEmpty())
    <p>No reports found.</p>
@else
<table class="table table-striped">
    <thead>
        <tr>
            <th>Submitted</th>
            <th>Council</th>
            <th>Email</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($reports as $report)
            <tr>
                <td>{{ $report->created_at->format('Y-m-d') }}</td>
                <td>{{ optional($report->council)->name ?? 'Unknown' }}</td>
                <td>{{ $report->contact_email }}</td>
                <td><a class="btn btn-sm btn-primary" href="{{ route('whistleblower-reports.show', $report) }}">Review</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $reports->links() }}
@endif
@endsection

@extends('layouts.admin')

@section('title', 'Figure Submissions')

@section('content')
<h1>Pending Figure Submissions</h1>
@if($submissions->isEmpty())
    <p>No submissions found.</p>
@else
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Council</th>
                <th>Year</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $submission)
                <tr>
                    <td>{{ optional($submission->council)->name ?? 'Unknown' }}</td>
                    <td>{{ $submission->financial_year }}</td>
                    <td>{{ $submission->created_at->format('Y-m-d') }}</td>
                    <td><a class="btn btn-sm btn-primary" href="{{ route('figure-submissions.show', $submission) }}">Review</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $submissions->links() }}
@endif
@endsection

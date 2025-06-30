<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Council Finance Counters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
<div id="app" class="container py-5">
    <h1 class="mb-4">Council Finance Counters</h1>
    <div class="mb-5">
        <h2 class="h4">Total Debt Across All Councils</h2>
        <div class="display-5 fw-bold">@{{ formattedDebt }}</div>
    </div>
    <div class="mb-3">
        <input type="text" class="form-control" placeholder="Search for a council" v-model="searchQuery" @input="searchCouncils"/>
        <ul class="list-group mt-2" v-if="councils.length">
            <li class="list-group-item" v-for="council in councils" :key="council.id">
                <a :href="'/council/' + council.slug">@{{ council.name }}</a>
            </li>
        </ul>
    </div>
    <a href="/admin" class="btn btn-primary mt-4">Admin Login</a>
</div>
</body>
</html>

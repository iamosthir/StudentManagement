<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - Student Management System</title>

    <!-- Vite Assets -->
    @vite(['resources/js/admin/app.js'])
</head>
<body>
    <div id="admin-app"></div>
</body>
</html>

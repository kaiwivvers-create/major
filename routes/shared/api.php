<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/api/search/companies', function (Request $request) {
    $q = $request->query('q', '');
    return DB::table('partner_companies')
        ->where('name', 'like', "%{$q}%")
        ->limit(10)
        ->get(['id', 'name', 'address']);
})->middleware('auth');

Route::get('/api/search/students', function (Request $request) {
    $q = $request->query('q', '');
    return DB::table('users')
        ->where('role', 'student')
        ->where('name', 'like', "%{$q}%")
        ->limit(10)
        ->get(['id', 'name', 'nis']);
})->middleware('auth');

Route::get('/api/stats/attendance-overview', function (Request $request) {
    $days = (int) $request->query('days', 7);
    $stats = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = now('Asia/Jakarta')->subDays($i)->toDateString();
        $stats[] = [
            'date' => $date,
            'present' => DB::table('attendances')->whereDate('attendance_date', $date)->where('status', 'present')->count(),
            'late' => DB::table('attendances')->whereDate('attendance_date', $date)->where('status', 'late')->count(),
            'sick' => DB::table('attendance_excuses')->whereDate('attendance_date', $date)->where('status', 'approved')->where('absence_type', 'sick')->count(),
        ];
    }
    return response()->json($stats);
})->middleware('auth');

Route::post('/api/chatbot/query', function (Request $request) {
    $request->validate(['message' => 'required|string|max:500']);
    return response()->json(get_chatbot_response($request->message, $request->user()));
})->middleware('auth');

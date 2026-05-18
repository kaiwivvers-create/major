<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $schoolPhoto = null;
    $landingCompanies = collect();
    $makeKey = function (string $name, string $address): string {
        return mb_strtolower(trim($name) . '||' . trim($address));
    };

    $placementRows = collect();
    if (Schema::hasTable('student_profiles')) {
        $placementRows = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.student_id')
            ->where('u.role', User::ROLE_STUDENT)
            ->whereNotNull('sp.pkl_place_name')
            ->where('sp.pkl_place_name', '<>', '')
            ->groupBy('sp.pkl_place_name', 'sp.pkl_place_address')
            ->selectRaw("
                sp.pkl_place_name as company_name,
                sp.pkl_place_address as company_address,
                COUNT(DISTINCT sp.student_id) as total_students
            ")
            ->get();
    }

    $placementByKey = $placementRows->keyBy(function ($row) use ($makeKey) {
        $name = trim((string) ($row->company_name ?? ''));
        $address = trim((string) ($row->company_address ?? '')) ?: '-';
        return $makeKey($name, $address);
    });

    $merged = collect();

    if (Schema::hasTable('partner_companies')) {
        $partnerSelect = ['name', 'address'];
        $optionalColumns = ['logo_url', 'contact_person', 'contact_phone', 'contact_email', 'website_url', 'is_active'];
        foreach ($optionalColumns as $column) {
            if (Schema::hasColumn('partner_companies', $column)) {
                $partnerSelect[] = $column;
            }
        }

        $partnerQuery = DB::table('partner_companies')
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->select($partnerSelect)
            ->orderBy('name');
        if (in_array('is_active', $partnerSelect, true)) {
            $partnerQuery->where('is_active', true);
        }
        if (in_array('address', $partnerSelect, true)) {
            $partnerQuery->orderBy('address');
        }

        $partnerRows = $partnerQuery->get();
        foreach ($partnerRows as $partner) {
            $name = trim((string) ($partner->name ?? ''));
            if ($name === '') {
                continue;
            }
            $address = trim((string) ($partner->address ?? '')) ?: '-';
            $key = $makeKey($name, $address);
            $placement = $placementByKey->get($key);

            $merged[$key] = (object) [
                'company_name' => $name,
                'company_address' => $address,
                'logo_url' => data_get($partner, 'logo_url'),
                'contact_person' => data_get($partner, 'contact_person'),
                'contact_phone' => data_get($partner, 'contact_phone'),
                'contact_email' => data_get($partner, 'contact_email'),
                'website_url' => data_get($partner, 'website_url'),
                'total_students' => (int) data_get($placement, 'total_students', 0),
            ];
        }
    }

    foreach ($placementRows as $row) {
        $name = trim((string) ($row->company_name ?? ''));
        if ($name === '') {
            continue;
        }
        $address = trim((string) ($row->company_address ?? '')) ?: '-';
        $key = $makeKey($name, $address);

        if ($merged->has($key)) {
            $merged[$key]->total_students = (int) ($row->total_students ?? 0);
            continue;
        }

        $merged[$key] = (object) [
            'company_name' => $name,
            'company_address' => $address,
            'logo_url' => null,
            'contact_person' => null,
            'contact_phone' => null,
            'contact_email' => null,
            'website_url' => null,
            'total_students' => (int) ($row->total_students ?? 0),
        ];
    }

    $landingCompanies = $merged
        ->sortByDesc(fn ($row) => (int) ($row->total_students ?? 0))
        ->sortBy(fn ($row) => mb_strtolower((string) ($row->company_name ?? '')))
        ->take(12)
        ->values();

    return view('welcome', [
        'schoolPhoto' => $schoolPhoto,
        'landingCompanies' => $landingCompanies,
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $input = $request->validate([
            'nis' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $identifier = trim($input['nis']);

        $user = User::query()
            ->when(Schema::hasColumn('users', 'nis'), fn($q) => $q->where('nis', $identifier))
            ->orWhere('email', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        $passwordIsValid = false;
        try {
            if ($user && Hash::check($input['password'], $user->password)) {
                $passwordIsValid = true;
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['nis' => 'This account has an insecure password format. Please contact admin to reset it using artisan user:fix-password.'])->onlyInput('nis');
        }

        if ($passwordIsValid) {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'nis' => 'The provided credentials do not match our records.',
        ])->onlyInput('nis');
    })->name('login.store');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardRouteDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_for_student_user(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.student'));

        $response->assertOk();
    }
}

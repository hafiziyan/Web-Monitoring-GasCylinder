<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Homepage mengarahkan guest ke login dan user login ke dashboard.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_diarahkan_ke_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    }
}

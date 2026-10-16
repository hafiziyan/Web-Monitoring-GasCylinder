<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login_ketika_membuka_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Gas Cylinder Monitoring System')
            ->assertSee($admin->name)
            ->assertSee('admin');
    }

    public function test_operator_dapat_membuka_dashboard(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('operator');
    }

    public function test_operator_tidak_dapat_membuka_halaman_admin(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_dapat_membuka_halaman_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_user_dapat_logout(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_user_dapat_login_dan_diarahkan_ke_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_user_nonaktif_tidak_dapat_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Operator,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_redirects_each_role_to_its_home(): void
    {
        $cases = [
            User::ROLE_KASIR => route('kasir.pos'),
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_PEMILIK => route('pemilik.dashboard'),
        ];

        foreach ($cases as $role => $home) {
            $user = $this->user($role);

            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect($home);

            $this->post(route('logout'));
        }
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->user();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_role_middleware_blocks_other_roles(): void
    {
        $this->actingAs($this->user(User::ROLE_KASIR));
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('pemilik.dashboard'))->assertForbidden();

        $this->actingAs($this->user(User::ROLE_ADMIN));
        $this->get(route('pemilik.laporan.penjualan'))->assertForbidden();
        $this->get(route('kasir.pos'))->assertForbidden();
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->update(['status' => false]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

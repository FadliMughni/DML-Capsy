<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** PBI 14 - Login dan Hak Akses Backoffice (RBAC) */
class BackofficeAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_and_has_csrf_token(): void
    {
        $this->get(route('backoffice.login'))
            ->assertOk()
            ->assertSee('Login Backoffice')
            ->assertSee('name="_token"', false);
    }

    public function test_public_footer_links_to_staff_login(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Login Staf')
            ->assertSee('href="'.route('backoffice.login').'"', false);
    }

    public function test_marketing_is_redirected_to_marketing_dashboard_after_login(): void
    {
        $user = User::factory()->marketing()->create();

        $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('backoffice.marketing.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('backoffice.marketing.dashboard'))->assertOk()->assertSee('Dashboard Marketing');
    }

    public function test_gudang_is_redirected_to_gudang_dashboard_after_login(): void
    {
        $user = User::factory()->gudang()->create();

        $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('backoffice.gudang.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('backoffice.gudang.dashboard'))->assertOk()->assertSee('Dashboard Gudang');
    }

    public function test_member_cannot_login_to_backoffice(): void
    {
        $user = User::factory()->member()->create();

        // Tanpa header "from": harus tetap kembali ke halaman login, bukan beranda.
        $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('backoffice.login'))
            ->assertSessionHasErrors(['email' => 'Akun ini tidak memiliki akses ke backoffice.']);

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_with_indonesian_message(): void
    {
        $user = User::factory()->marketing()->create();

        $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->marketing()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'salah']);
        }

        $response = $this->post(route('backoffice.login.store'), ['email' => $user->email, 'password' => 'password']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_password_is_stored_hashed(): void
    {
        $user = User::factory()->marketing()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->marketing()->create();

        $this->actingAs($user)->post(route('backoffice.logout'))
            ->assertRedirect(route('backoffice.login'));

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_when_opening_backoffice(): void
    {
        $this->get(route('backoffice.marketing.dashboard'))->assertRedirect(route('backoffice.login'));
        $this->get(route('backoffice.gudang.dashboard'))->assertRedirect(route('backoffice.login'));
    }

    public function test_backoffice_home_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->marketing()->create())
            ->get(route('backoffice.home'))->assertRedirect(route('backoffice.marketing.dashboard'));

        $this->actingAs(User::factory()->gudang()->create())
            ->get(route('backoffice.home'))->assertRedirect(route('backoffice.gudang.dashboard'));
    }

    public function test_member_gets_403_on_all_backoffice_pages(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get(route('backoffice.home'))->assertForbidden();
        $this->actingAs($member)->get(route('backoffice.marketing.dashboard'))->assertForbidden();
        $this->actingAs($member)->get(route('backoffice.gudang.dashboard'))->assertForbidden();
    }

    public function test_gudang_gets_403_on_marketing_pages_and_vice_versa(): void
    {
        $this->actingAs(User::factory()->gudang()->create())
            ->get(route('backoffice.marketing.dashboard'))->assertForbidden();

        $this->actingAs(User::factory()->marketing()->create())
            ->get(route('backoffice.gudang.dashboard'))->assertForbidden();
    }

    public function test_menu_shown_matches_role(): void
    {
        $this->actingAs(User::factory()->gudang()->create())
            ->get(route('backoffice.gudang.dashboard'))
            ->assertOk()
            ->assertDontSee(route('backoffice.marketing.dashboard'));

        $this->actingAs(User::factory()->marketing()->create())
            ->get(route('backoffice.marketing.dashboard'))
            ->assertOk()
            ->assertDontSee(route('backoffice.gudang.dashboard'));
    }
}

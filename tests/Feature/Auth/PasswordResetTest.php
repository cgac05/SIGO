<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Mail::shouldReceive('send')->once();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('password_reset_step', 'code');
        $response->assertSessionHas('password_reset_email', $user->email);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_link_requires_registered_email(): void
    {
        Mail::shouldReceive('send')->never();

        $response = $this->post('/forgot-password', [
            'email' => 'no-existe@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_google_linked_accounts_show_warning_and_do_not_send_code(): void
    {
        Mail::shouldReceive('send')->never();

        $user = User::factory()->create([
            'google_id' => 'google-account-123',
        ]);

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('warning');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_code_can_be_verified(): void
    {
        Mail::shouldReceive('send')->once();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'token' => Hash::make('123456'),
                'created_at' => now(),
            ]);

        $response = $this->post('/forgot-password/verify-code', [
            'codigo' => '123456',
        ]);

        $response->assertRedirect(route('password.reset', [
            'token' => '123456',
            'email' => $user->email,
        ]));
    }

    public function test_password_can_be_reset_with_valid_code(): void
    {
        Mail::shouldReceive('send')->once();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'token' => Hash::make('123456'),
                'created_at' => now(),
            ]);

        $this->post('/forgot-password/verify-code', [
            'codigo' => '123456',
        ]);

        $response = $this->post('/reset-password', [
            'token' => '123456',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));
    }
}

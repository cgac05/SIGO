<?php

namespace Tests\Feature\Auth;

use App\Models\Beneficiario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'apellido_paterno' => 'Lopez',
            'apellido_materno' => 'Ramirez',
            'curp' => 'ABCD050101HDFLRN09',
            'telefono' => '(311) 123-4567',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'acepta_privacidad' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('Usuarios', [
            'email' => 'test@example.com',
            'tipo_usuario' => 'Beneficiario',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('Beneficiarios', [
            'fk_id_usuario' => $user->id_usuario,
            'curp' => 'ABCD050101HDFLRN09',
        ]);

        $this->assertInstanceOf(Beneficiario::class, $user->beneficiario);
    }
}

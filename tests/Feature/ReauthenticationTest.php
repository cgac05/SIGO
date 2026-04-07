<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReauthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Verificar re-autenticación con contraseña correcta
     */
    public function test_reauth_with_correct_password()
    {
        // Crear usuario de prueba
        $usuario = Usuario::factory()->create([
            'password' => Hash::make('password123'),
            'two_factor_enabled' => false,
        ]);

        // Autenticarse
        $this->actingAs($usuario);

        // Enviar request de re-autenticación
        $response = $this->postJson(route('auth.reauth-verify'), [
            'password' => 'password123',
            'otp' => null,
        ]);

        // Validations
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'reauth_token',
            'usuario' => ['nombre', 'apellidos'],
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test: Rechazar re-autenticación con contraseña incorrecta
     */
    public function test_reauth_with_incorrect_password()
    {
        $usuario = Usuario::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        $response = $this->postJson(route('auth.reauth-verify'), [
            'password' => 'wrongpassword',
            'otp' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Contraseña incorrecta.');
    }

    /**
     * Test: Rechazar re-autenticación sin sesión
     */
    public function test_reauth_without_session()
    {
        $response = $this->postJson(route('auth.reauth-verify'), [
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test: Validar estructura del modal component
     */
    public function test_reauth_modal_component_renders()
    {
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario);

        $view = $this->blade(
            '@include("components.modals.reauth-signature", ["solicitudId" => "SIGO-2026-00001", "onlyPassword" => false])'
        );

        $view->assertSeeInOrder([
            'Verificación de identidad',
            'Re-autenticación requerida para firmar',
            'Contraseña',
            'Código de verificación 2FA',
            'Verificar identidad',
        ]);
    }
}

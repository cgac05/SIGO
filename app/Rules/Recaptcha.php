<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /**
     * Ejecuta la regla de validación.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Realizar la petición a Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret_key'),
            'response' => $value,
        ]);

        $body = $response->json();

        // 2. Verificar si la respuesta fue exitosa y el score es suficiente
        // El 'score' solo existe en reCAPTCHA v3 (0.0 a 1.0)
        if (!isset($body['success']) || !$body['success'] || ($body['score'] ?? 0) < 0.5) {
            $fail('La validación de seguridad (CAPTCHA) ha fallado. Inténtalo de nuevo.');
        }
    }
}
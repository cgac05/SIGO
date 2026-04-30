<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
            'display_name' => is_string($this->input('display_name')) ? preg_replace('/\s+/u', ' ', trim($this->input('display_name'))) : $this->input('display_name'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isGoogleLinked = filled($user?->google_id);

        $emailRules = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
        ];

        if ($isGoogleLinked) {
            $emailRules[] = Rule::in([mb_strtolower((string) ($user?->email ?? ''))]);
        } else {
            $emailRules[] = Rule::unique('Usuarios', 'email')
                ->ignore($user?->id_usuario, 'id_usuario')
                ->where(fn ($query) => $query->where('activo', 1));
        }

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
        ];
    }

    public function messages(): array
    {
        return [
            'email.in' => 'El correo no puede modificarse porque la cuenta está vinculada con Google.',
        ];
    }
}

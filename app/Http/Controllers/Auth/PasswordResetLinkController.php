<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificacionCodigoRestablecimientoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password', [
            'step' => session('password_reset_step', 'email'),
            'resetEmail' => session('password_reset_email'),
        ]);
    }

    /**
     * Handle an incoming password reset code request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->validated()['email']));
        $usuario = User::query()->where('email', $email)->first();

        if (! $usuario) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('El correo electrónico ingresado no se encuentra asociado a ninguna cuenta.')]);
        }

        if (! empty($usuario->google_id)) {
            return back()
                ->withInput($request->only('email'))
                ->with('warning', __('Esta cuenta fue creada o vinculada con Google. Para acceder a su cuenta, inicie sesión con Google usando la misma dirección de correo electrónico.'));
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $usuario->email],
            [
                'token' => Hash::make($codigo),
                'created_at' => now(),
            ]
        );

        if (! NotificacionCodigoRestablecimientoService::enviarCodigo($usuario, $codigo)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('No fue posible enviar el código de restablecimiento. Intente nuevamente.')]);
        }

        $request->session()->put([
            'password_reset_step' => 'code',
            'password_reset_email' => $usuario->email,
        ]);

        return back()->with('status', __('Se ha enviado un código de restablecimiento al correo proporcionado.'));
    }

    /**
     * Verify the code entered by the user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'codigo.required' => __('El código de restablecimiento es obligatorio.'),
            'codigo.size' => __('El código debe contener exactamente 6 dígitos.'),
            'codigo.regex' => __('El código debe contener solo números.'),
        ]);

        $email = (string) $request->session()->get('password_reset_email', '');

        if ($email === '') {
            return redirect()
                ->route('password.request')
                ->withErrors(['codigo' => __('Primero debe solicitar un código de restablecimiento.')]);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return back()->withErrors(['codigo' => __('El código solicitado ya no está disponible. Solicite uno nuevo.')]);
        }

        $expiraEnMinutos = (int) config('auth.passwords.users.expire', 60);
        $creadoEn = Carbon::parse($record->created_at);

        if ($creadoEn->copy()->addMinutes($expiraEnMinutos)->isPast()) {
            return back()->withErrors(['codigo' => __('El código ha expirado. Solicite uno nuevo.')]);
        }

        if (! Hash::check($validated['codigo'], $record->token)) {
            return back()->withErrors(['codigo' => __('El código ingresado no es válido.')]);
        }

        $request->session()->forget(['password_reset_step', 'password_reset_email']);

        return redirect()
            ->route('password.reset', [
                'token' => $validated['codigo'],
                'email' => $email,
            ])
            ->with('status', __('Código verificado correctamente. Ahora puede establecer una nueva contraseña.'));
    }
}

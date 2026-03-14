<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CurpValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $curp = mb_strtoupper(trim((string) $value));

        if (! preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', $curp)) {
            $fail('La CURP no cumple con el formato oficial.');

            return;
        }

        $birthDate = $this->extractBirthDate($curp);

        if (! $birthDate) {
            $fail('La fecha de nacimiento contenida en la CURP no es válida.');

            return;
        }

        $age = $birthDate->age;

        if ($age < 12 || $age > 29) {
            $fail('Solo pueden registrarse personas de 12 a 29 años conforme a la ley de juventud vigente.');
        }
    }

    public static function extractBirthDate(string $curp): ?Carbon
    {
        $year = (int) substr($curp, 4, 2);
        $month = (int) substr($curp, 6, 2);
        $day = (int) substr($curp, 8, 2);
        $currentYear = (int) now()->format('y');
        $century = $year > $currentYear ? 1900 : 2000;

        try {
            return Carbon::createFromDate($century + $year, $month, $day)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
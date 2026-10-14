<?php

namespace App\Services;

class CredencialesService
{
    protected function stripAccents(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        return preg_replace('/[^A-Za-z0-9\s\-]/', '', $value);
    }

    protected function firstToken(string $value): string
    {
        $clean = $this->stripAccents($value);
        $parts = preg_split('/[\s\-]+/', trim($clean));
        return $parts[0] ?? '';
    }

    public function buildUsername(string $apellidos, string $nombres, array $existing, ?string $email = null): string
    {
        // Prefer local part of email when available and valid
        if ($email && str_contains($email, '@')) {
            $local = strtolower(explode('@', $email)[0]);
            $local = $this->stripAccents($local);
            $local = preg_replace('/[^a-z0-9\.]+/', '.', $local);
            $local = trim($local, '.');
            if ($local !== '') {
                $candidate = $local;
                $i = 2;
                while (in_array($candidate, $existing, true)) {
                    $candidate = $local . '.' . $i;
                    $i++;
                }
                return $candidate;
            }
        }

        // Fallback to name-based username
        $base = strtolower($this->firstToken($apellidos) . '.' . $this->firstToken($nombres));
        $core = preg_replace('/[^a-z0-9\.]+/', '.', $base);
        $core = trim($core, '.');
        if ($core === '') {
            $core = 'user';
        }
        $candidate = $core;
        $i = 2;
        while (in_array($candidate, $existing, true)) {
            $candidate = $core . '.' . $i;
            $i++;
        }
        return $candidate;
    }

    public function makeTeacherPassword(string $anio, string $mes, int $correlativo): string
    {
        $seq = str_pad((string) $correlativo, 3, '0', STR_PAD_LEFT);
        return "D{$anio}{$mes}{$seq}";
    }

    public function makeStudentPassword(string $anio, string $mes, int $correlativo): string
    {
        $seq = str_pad((string) $correlativo, 3, '0', STR_PAD_LEFT);
        return "E{$anio}{$mes}{$seq}";
    }
}

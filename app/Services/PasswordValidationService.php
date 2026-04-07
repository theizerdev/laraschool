<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PasswordValidationService
{
    /**
     * Valida que una contraseña cumpla con los requisitos de seguridad
     */
    public function validatePasswordStrength(string $password, string $username = null, string $email = null): array
    {
        $errors = [];
        
        // Verificar longitud mínima
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        
        // Verificar al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula.';
        }
        
        // Verificar al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula.';
        }
        
        // Verificar al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número.';
        }
        
        // Verificar al menos un carácter especial
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial.';
        }
        
        // Verificar que no contenga el nombre de usuario si se proporciona
        if ($username && stripos($password, $username) !== false) {
            $errors[] = 'La contraseña no debe contener el nombre de usuario.';
        }
        
        // Verificar que no contenga el email si se proporciona
        if ($email && stripos($password, $email) !== false) {
            $errors[] = 'La contraseña no debe contener el correo electrónico.';
        }
        
        // Verificar que no sea una contraseña común
        $commonPasswords = [
            'password', 'contraseña', '12345678', 'qwerty123', 'admin123', 
            'welcome1', 'letmein1', 'monkey12', 'sunshine', 'master123'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'La contraseña es demasiado común. Elija una contraseña más segura.';
        }
        
        return $errors;
    }
    
    /**
     * Valida la contraseña usando el validador de Laravel
     */
    public function validateWithLaravelValidator(string $password, string $username = null, string $email = null): bool
    {
        $validationRules = [
            'password' => [
                'required',
                'min:8',
                'regex:/[A-Z]/',    // Al menos una mayúscula
                'regex:/[a-z]/',    // Al menos una minúscula
                'regex:/[0-9]/',    // Al menos un número
                'regex:/[^A-Za-z0-9]/', // Al menos un carácter especial
            ]
        ];
        
        $data = ['password' => $password];
        
        $validator = Validator::make($data, $validationRules);
        
        if ($validator->fails()) {
            return false;
        }
        
        // Validaciones adicionales que no se pueden hacer con reglas estándar
        $customErrors = $this->validatePasswordStrength($password, $username, $email);
        
        return count($customErrors) === 0;
    }
    
    /**
     * Obtiene los requisitos de contraseña para mostrar al usuario
     */
    public function getPasswordRequirements(): array
    {
        return [
            'longitud_minima' => 'Al menos 8 caracteres',
            'mayusculas' => 'Una letra mayúscula',
            'minusculas' => 'Una letra minúscula',
            'numeros' => 'Un número',
            'caracteres_especiales' => 'Un carácter especial',
            'no_nombre_usuario' => 'No debe contener el nombre de usuario',
            'no_email' => 'No debe contener el correo electrónico',
            'no_comunes' => 'No debe ser una contraseña común'
        ];
    }
}
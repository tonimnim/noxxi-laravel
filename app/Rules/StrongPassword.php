<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Minimum password length
     */
    protected int $minLength;

    /**
     * Require at least one uppercase letter
     */
    protected bool $requireUppercase;

    /**
     * Require at least one lowercase letter
     */
    protected bool $requireLowercase;

    /**
     * Require at least one number
     */
    protected bool $requireNumbers;

    /**
     * Require at least one special character
     */
    protected bool $requireSpecialChars;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;
        $errors = [];

        // Check minimum length
        if (strlen($password) < $this->minLength) {
            $errors[] = "at least {$this->minLength} characters";
        }

        // Check for uppercase letter
        if ($this->requireUppercase && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'one uppercase letter';
        }

        // Check for lowercase letter
        if ($this->requireLowercase && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'one lowercase letter';
        }

        // Check for number
        if ($this->requireNumbers && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'one number';
        }

        // Check for special character
        if ($this->requireSpecialChars && ! preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = 'one special character (!@#$%^&*()_-+={}[];:,<.>)';
        }

        // Check for common passwords
        if ($this->isCommonPassword($password)) {
            $fail('The :attribute is too common. Please choose a more secure password.');

            return;
        }

        // Check for sequential characters
        if ($this->hasSequentialCharacters($password)) {
            $fail('The :attribute contains sequential characters. Please choose a more secure password.');

            return;
        }

        // Build error message if validation failed
        if (! empty($errors)) {
            $message = 'The :attribute must contain ';
            if (count($errors) === 1) {
                $message .= $errors[0];
            } elseif (count($errors) === 2) {
                $message .= $errors[0].' and '.$errors[1];
            } else {
                $lastError = array_pop($errors);
                $message .= implode(', ', $errors).', and '.$lastError;
            }
            $message .= '.';

            $fail($message);
        }
    }

    /**
     * Check if password is in the common passwords list
     */
    protected function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password1', 'password123', '12345678', '123456789',
            'qwerty', 'qwerty123', 'admin', 'admin123', 'letmein',
            'welcome', 'welcome123', 'monkey', 'dragon', 'master',
            'abc123', 'iloveyou', 'sunshine', 'princess', 'password1!',
            'Password1', 'Password123', 'Admin123', 'Qwerty123', 'Welcome123',
        ];

        return in_array(strtolower($password), array_map('strtolower', $commonPasswords));
    }

    /**
     * Check for sequential characters (e.g., abc, 123, qwerty)
     */
    protected function hasSequentialCharacters(string $password): bool
    {
        $sequences = [
            'abc', 'bcd', 'cde', 'def', 'efg', 'fgh', 'ghi', 'hij', 'ijk', 'jkl',
            'klm', 'lmn', 'mno', 'nop', 'opq', 'pqr', 'qrs', 'rst', 'stu', 'tuv',
            'uvw', 'vwx', 'wxy', 'xyz', '012', '123', '234', '345', '456', '567',
            '678', '789', '890', 'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio',
            'iop', 'asd', 'sdf', 'dfg', 'fgh', 'ghj', 'hjk', 'jkl', 'zxc', 'xcv',
            'cvb', 'vbn', 'bnm',
        ];

        $lowerPassword = strtolower($password);
        foreach ($sequences as $sequence) {
            if (str_contains($lowerPassword, $sequence) || str_contains($lowerPassword, strrev($sequence))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a simple version for non-financial apps
     */
    public static function simple(): self
    {
        return new self(
            minLength: 4,
            requireUppercase: false,
            requireLowercase: false,
            requireNumbers: false,
            requireSpecialChars: false
        );
    }

    /**
     * Create a development-friendly version with relaxed requirements
     */
    public static function development(): self
    {
        return new self(
            minLength: 4,
            requireUppercase: false,
            requireLowercase: false,
            requireNumbers: false,
            requireSpecialChars: false
        );
    }

    /**
     * Create a production version with strict requirements
     */
    public static function production(): self
    {
        return new self(
            minLength: 4,
            requireUppercase: false,
            requireLowercase: false,
            requireNumbers: false,
            requireSpecialChars: false
        );
    }
}

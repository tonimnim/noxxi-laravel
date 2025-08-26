<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class OrganizerRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $passwordRule = app()->environment('production')
            ? StrongPassword::production()
            : StrongPassword::development();

        return [
            // Personal Information
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
                'unique:users,phone_number',
            ],
            'password' => ['required', 'string', 'confirmed', $passwordRule],
            'password_confirmation' => ['required', 'string'],

            // Business Information
            'business_name' => ['required', 'string', 'max:255'],
            'business_country' => ['sometimes', 'string', 'size:2'],
            'business_timezone' => ['sometimes', 'timezone'],
            'default_currency' => ['sometimes', 'string', 'in:'.implode(',', array_keys(config('currencies.supported', [])))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Please provide your full name.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Please provide a valid phone number with country code.',
            'phone_number.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'business_name.required' => 'Business name is required.',
            'business_name.max' => 'Business name cannot exceed 255 characters.',
            'business_country.size' => 'Please provide a valid 2-letter country code.',
            'business_timezone.timezone' => 'Please provide a valid timezone.',
            'default_currency.in' => 'Selected currency is not supported.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Sanitize email
        if ($this->has('email')) {
            $data['email'] = strtolower(trim($this->email));
        }

        // Sanitize names
        if ($this->has('full_name')) {
            $data['full_name'] = trim($this->full_name);
        }
        if ($this->has('business_name')) {
            $data['business_name'] = trim($this->business_name);
        }

        // Sanitize phone number
        if ($this->has('phone_number')) {
            $data['phone_number'] = preg_replace('/[\s-]/', '', $this->phone_number);
        }

        // Set defaults for business fields if not provided
        if (! $this->has('business_country')) {
            $data['business_country'] = 'KE';
        }
        if (! $this->has('business_timezone')) {
            $data['business_timezone'] = 'Africa/Nairobi';
        }
        if (! $this->has('default_currency')) {
            $data['default_currency'] = 'KES';
        }

        // Force role to organizer
        $data['role'] = 'organizer';

        $this->merge($data);
    }

    /**
     * Get validated data with additional processing.
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        // Remove password_confirmation from the validated data
        if (is_array($data) && isset($data['password_confirmation'])) {
            unset($data['password_confirmation']);
        }

        // Add default values for organizer profile
        if (is_array($data)) {
            $data['commission_rate'] = $data['commission_rate'] ?? 10.00;
            $data['settlement_period_days'] = $data['settlement_period_days'] ?? 7;
            $data['is_active'] = true;
        }

        return $data;
    }
}

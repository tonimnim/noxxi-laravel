<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'role' => ['sometimes', 'string', Rule::in(['user', 'organizer'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Please provide your full name.',
            'full_name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Please provide a valid phone number with country code.',
            'phone_number.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Please confirm your password.',
            'role.in' => 'Invalid account type selected.',
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

        // Sanitize full name
        if ($this->has('full_name')) {
            $data['full_name'] = trim($this->full_name);
        }

        // Sanitize phone number
        if ($this->has('phone_number')) {
            $data['phone_number'] = preg_replace('/[\s-]/', '', $this->phone_number);
        }

        // Set default role if not provided
        if (! $this->has('role')) {
            $data['role'] = 'user';
        }

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

        return $data;
    }
}

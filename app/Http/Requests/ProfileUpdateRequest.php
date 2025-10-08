<?php 

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]+$/'],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]*$/'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]+$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:50',
                Rule::unique(User::class, 'email')->ignore($userId),
                'not_regex:/[<>"\s]/',
            ],
            'phone' => ['nullable', 'string', 'regex:/^\+?\d{7,15}$/'],
            'address' => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9\s,\.\-]+$/'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],

            // 🔒 Strong password rules (applies only if user wants to change password)
            'password' => [
                'nullable', // not required unless user wants to change
                'confirmed',
                Rules\Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            // Names
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex' => 'First name can only contain letters, spaces, and hyphens.',

            'last_name.required' => 'Please enter your last name.',
            'last_name.regex' => 'Last name can only contain letters, spaces, and hyphens.',

            'middle_name.regex' => 'Middle name can only contain letters, spaces, and hyphens.',

            // Phone
            'phone.regex' => 'Enter a valid phone number (7–15 digits).',

            // Address
            'address.regex' => 'Only letters, numbers, spaces, and , . - are allowed.',
            'address.max' => 'Address is too long (max 150 characters).',

            // Email
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email (example@domain.com).',
            'email.max' => 'Email must be under 50 characters.',
            'email.unique' => 'This email is already registered.',

            // Password
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least :min characters.',
            'password.confirmed' => 'Passwords do not match.',
            'password.letters' => 'Password must contain at least one letter.',
            'password.numbers' => 'Password must contain at least one number.',
            'password.mixed' => 'Password must contain both uppercase and lowercase letters.',
            'password.symbols' => 'Password must contain at least one special character.',
        ];
    }
}

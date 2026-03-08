<?php

namespace App\DTOs\Auth;

use App\DTOs\BaseDTO;

class RegisterDTO extends BaseDTO
{
    public string $username;
    public string $firstName;
    public string $lastName;
    public string $name; // Combined first + last name
    public string $email;
    public ?string $phone;
    public string $password;
    public bool $isCandidate;
    public bool $isEmployer;
    public string $roleSlug;

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        return [
            'username' => 'required|string|unique:users|min:3|max:20',
            'first_name' => 'required|string|max:25',
            'last_name' => 'required|string|max:25',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|unique:users|regex:/^[0-9]{10,15}$/',
            'password' => 'required|string|min:8|confirmed',
            'candidate' => 'required|boolean',
            'employer' => 'required|boolean',
        ];
    }

    /**
     * Custom validation messages
     */
    protected function messages(): array
    {
        return [
            'username.required' => 'Username is required',
            'username.unique' => 'This username is already taken',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 20 characters',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'phone.regex' => 'Phone number must be 10-15 digits',
            'phone.unique' => 'This phone number is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'candidate.required' => 'Please select a user type',
            'employer.required' => 'Please select a user type',
        ];
    }

    /**
     * Fill DTO with validated data
     */
    protected function fill(array $data): void
    {
        $this->username = $data['username'];
        $this->firstName = $data['first_name'];
        $this->lastName = $data['last_name'];
        
        // Merge first and last name
        $this->name = trim($this->firstName . ' ' . $this->lastName);
        
        $this->email = $data['email'];
        $this->phone = $data['phone'] ?? null;
        $this->password = $data['password'];
        $this->isCandidate = $data['candidate'];
        $this->isEmployer = $data['employer'];

        //  Determine role slug
        $this->roleSlug = $this->isCandidate ? 'candidate' : 'employer';
    }

    /**
     * Convert DTO to array for database insertion
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'name' => $this->name, // Combined name
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password, // Will be hashed in service
        ];
    }

    /**
     * Get role slug
     */
    public function getRoleSlug(): string
    {
        return $this->roleSlug;
    }

    /**
     * Check if exactly one role is selected
     */
    public function isValidRoleSelection(): bool
    {
        return $this->isCandidate !== $this->isEmployer;
    }
}
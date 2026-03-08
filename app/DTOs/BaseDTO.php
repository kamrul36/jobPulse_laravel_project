<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseDTO
{
    /**
     * Validation rules for the DTO
     */
    abstract protected function rules(): array;

    /**
     * Custom validation messages (optional)
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Create DTO from request data
     */
    public static function fromRequest(array $data): static
    {
        $instance = new static();
        $validated = $instance->validate($data);
        $instance->fill($validated);
        
        return $instance;
    }

    /**
     * Validate the data
     */
    protected function validate(array $data): array
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Fill DTO properties with validated data
     */
    abstract protected function fill(array $data): void;

    /**
     * Convert DTO to array
     */
    abstract public function toArray(): array;
}
<?php

namespace App\DTOs\Job;

use App\DTOs\BaseDTO;

class CreateJobDTO extends BaseDTO
{
    public string $title;
    public int $categoryId;
    public ?string $description;
    public string $skills;
    public ?string $salary;
    public ?string $deadline;
    public ?string $openPosition;
    public string $location;
    public string $type;
    public ?string $experience;
    public bool $isFeatured;
    public string $createdBy;

    protected function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'skills' => 'required|string',
            'salary' => 'nullable|string',
            'deadline' => 'nullable|date',
            'open_position' => 'nullable|string',
            'location' => 'required|string',
            'type' => 'required|in:full_time,remote,part_time,project_basis,freelance',
            'experience' => 'nullable|string',
            'isFeatured' => 'nullable|boolean',
        ];
    }

    protected function fill(array $data): void
    {
        $this->title = $data['title'];
        $this->categoryId = $data['category_id'];
        $this->description = $data['description'] ?? null;
        $this->skills = $data['skills'];
        $this->salary = $data['salary'] ?? null;
        $this->deadline = $data['deadline'] ?? null;
        $this->openPosition = $data['open_position'] ?? null;
        $this->location = $data['location'];
        $this->type = $data['type'];
        $this->experience = $data['experience'] ?? null;
        $this->isFeatured = $data['isFeatured'] ?? false;
    }

    public function setCreatedBy(string $userId): void
    {
        $this->createdBy = $userId;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category_id' => $this->categoryId,
            'description' => $this->description,
            'skills' => $this->skills,
            'salary' => $this->salary,
            'deadline' => $this->deadline,
            'open_position' => $this->openPosition,
            'location' => $this->location,
            'type' => $this->type,
            'experience' => $this->experience,
            'isFeatured' => $this->isFeatured,
            'created_by' => $this->createdBy,
            'status' => 0, // Inactive by default
        ];
    }
}
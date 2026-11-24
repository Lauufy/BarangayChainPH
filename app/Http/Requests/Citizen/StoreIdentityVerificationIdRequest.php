<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdentityVerificationIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $draft = $this->session()->get('identity_verification.draft', []);

        return [
            'document_type' => ['required', 'string', Rule::in([
                'Philippine National ID',
                "Driver's License",
                'Passport',
                'UMID',
                'Postal ID',
                "Voter's ID",
                'PRC ID',
                'Barangay ID',
            ])],
            'document' => [
                Rule::requiredIf(! filled($draft['document_path'] ?? null)),
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            'document_back' => [
                Rule::requiredIf(! filled($draft['document_back_key'] ?? null)),
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'The ID must be a JPG, PNG, or PDF file.',
            'document.max' => 'The ID must not be larger than 5 MB.',
            'document_back.mimes' => 'The back of the ID must be a JPG, PNG, or PDF file.',
            'document_back.max' => 'The back of the ID must not be larger than 5 MB.',
        ];
    }
}

<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdentityVerificationSelfieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $draft = $this->session()->get('identity_verification.draft', []);

        return [
            'selfie' => [
                Rule::requiredIf(! filled($draft['selfie_path'] ?? null)),
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
            'selfie_source' => ['required', Rule::in(['camera', 'upload'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selfie.mimes' => 'The selfie must be a JPG or PNG photo.',
            'selfie.max' => 'The selfie must not be larger than 5 MB.',
        ];
    }
}

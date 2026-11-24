<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeIdentityVerificationRequest extends FormRequest
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
        return [
            'agree_truth' => ['accepted'],
            'agree_privacy' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agree_truth.accepted' => 'Please confirm that your information is true.',
            'agree_privacy.accepted' => 'Please agree to the data privacy notice.',
        ];
    }
}

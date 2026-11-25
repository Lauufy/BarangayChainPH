<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Validation\Rule;

class PermitCorrectionFields
{
    public static function for(ServiceRequest $application): array
    {
        $civilStatuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
        $fields = [
            'applicant_name' => ['label' => 'Full name', 'value' => $application->applicant_name, 'type' => 'text', 'rules' => ['string', 'max:255']],
            'age' => ['label' => 'Age', 'value' => $application->age, 'type' => 'number', 'rules' => ['integer', 'between:0,125']],
            'civil_status' => ['label' => 'Civil status', 'value' => $application->civil_status, 'type' => 'select', 'options' => $civilStatuses, 'rules' => [Rule::in($civilStatuses)]],
            'address' => ['label' => 'Address', 'value' => $application->address, 'type' => 'text', 'rules' => ['string', 'max:500']],
        ];
        $config = $application->serviceConfig();
        foreach ($config['fields'] ?? [] as $field) {
            $key = $field['key'];
            $fields[$key] = [
                ...$field,
                'value' => $application->details[$key] ?? match ($key) {
                    'purpose' => $application->purpose,
                    'contact_number', 'business_contact_number' => $application->contact_number,
                    default => null,
                },
                'rules' => $field['rules'] ?? match (true) {
                    ($field['other'] ?? false) => ['string', 'min:3', 'max:200'],
                    $field['type'] === 'select' => ['string', Rule::in($field['options'])],
                    $field['type'] === 'tel' => ['string', 'max:30'],
                    $field['type'] === 'textarea' => ['string', 'max:1000'],
                    default => ['string', 'max:255'],
                },
            ];
        }
        if (! isset($fields['contact_number']) && ! isset($fields['business_contact_number'])) {
            $fields['contact_number'] = ['label' => 'Contact number', 'type' => 'tel', 'value' => $application->contact_number, 'rules' => ['string', 'max:30']];
        }
        foreach (['birthdate' => 'Date of birth', 'birthplace' => 'Place of birth', 'nationality' => 'Nationality'] as $key => $label) {
            if (array_key_exists($key, $application->details ?? [])) {
                $fields[$key] = [
                    'label' => $label, 'value' => $application->details[$key], 'type' => $key === 'birthdate' ? 'date' : 'text',
                    'rules' => $key === 'birthdate' ? ['date_format:Y-m-d', 'before_or_equal:today'] : ['string', 'max:255'],
                ];
            }
        }
        if (! isset($fields['purpose']) && empty($config['purpose_template'])) {
            $fields['purpose'] = ['label' => 'Purpose', 'type' => 'text', 'value' => $application->purpose, 'rules' => ['string', 'max:500']];
        }
        $documents = $application->documents->keyBy('requirement_key');
        foreach (($config['requirements'] ?? []) + $documents->pluck('label', 'requirement_key')->all() as $key => $label) {
            $fields['file_'.$key] = [
                'label' => $label, 'type' => 'file', 'value' => $documents->get($key)?->original_name,
                'rules' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            ];
        }

        return $fields;
    }
}

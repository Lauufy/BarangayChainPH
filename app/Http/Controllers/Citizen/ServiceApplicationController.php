<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/** The 4-step application wizard. Each service is defined in config/barangay_services.php. */
class ServiceApplicationController extends Controller
{
    private const DISK = 'supabase';

    private const MAX_FILE_KB = 10240; // 10 MB

    public function create(Request $request, string $slug): View|RedirectResponse
    {
        $service = $this->service($slug);

        if ($this->pendingQuery($request->user(), $service)->exists()) {
            return $this->pendingBlocked();
        }

        $applicant = $this->applicant($request->user());

        if ($applicant === null) {
            return $this->profileIncomplete();
        }

        $errorKeys = $request->session()->get('errors')?->getBag('default')->keys() ?? [];

        return view('citizen.service-requests.create', [
            'slug' => $slug,
            'service' => $service,
            'steps' => [1 => 'Applicant information', 2 => 'Application details', 3 => 'Supporting documents', 4 => 'Review'],
            'startStep' => $this->startStep($errorKeys, $service),
            'sections' => $applicant['display'],
            'fields' => $this->formFields($service, $request->user()->name),
            'userName' => $request->user()->name,
            'address' => $applicant['address'],
            'maxMb' => self::MAX_FILE_KB / 1024,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $service = $this->service($slug);
        $user = $request->user();

        // Second safety check, in case the route middleware is ever changed
        if (! $user->isIdentityVerified()) {
            return redirect()
                ->route('citizen.identity-verification')
                ->with('warning', 'Please verify your identity before applying.');
        }

        // Applicant details always come from the verified profile, never from the form
        $applicant = $this->applicant($user);

        if ($applicant === null) {
            return $this->profileIncomplete();
        }

        $data = $request->validate(
            $this->rules($service),
            $this->messages($service),
            $this->attributes($service)
        );

        [$details, $purpose] = $this->resolveDetails($service, $data);

        $storedPaths = []; // so uploaded files can be removed if the save fails

        try {
            $serviceRequest = DB::transaction(function () use ($request, $user, $service, $applicant, $details, $purpose, &$storedPaths) {
                // Block a second open request for the same service
                if ($this->pendingQuery($user, $service)->lockForUpdate()->exists()) {
                    return null;
                }

                $serviceRequest = ServiceRequest::create([
                    'user_id' => $user->id,
                    'reference_number' => 'BRGY-'.now()->format('YmdHis').'-'.str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                    'service_type' => $service['type'],
                    'applicant_name' => $applicant['name'],
                    'address' => $applicant['address'],
                    'age' => $applicant['age'],
                    'civil_status' => $applicant['civil_status'],
                    'contact_number' => $applicant['contact_number'],
                    'purpose' => $purpose,
                    'details' => $details,
                    'status' => ServiceRequest::PENDING,
                ]);

                // Store each required upload in private storage
                foreach ($service['requirements'] as $key => $label) {
                    $file = $request->file("requirements.{$key}");
                    $path = $file->store("service-requirements/{$serviceRequest->id}", self::DISK);

                    if (! $path) {
                        throw new RuntimeException("The {$label} could not be stored.");
                    }

                    $storedPaths[] = $path;

                    $serviceRequest->documents()->create([
                        'requirement_key' => $key,
                        'label' => $label,
                        'disk' => self::DISK,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]);
                }

                // Open a ticket with an automatic first message
                $serviceRequest->ticket()
                    ->create(['user_id' => $user->id])
                    ->messages()
                    ->create([
                        'body' => "Your request {$serviceRequest->reference_number} was submitted. The barangay will post updates here.",
                        'is_system' => true,
                    ]);

                return $serviceRequest;
            });
        } catch (Throwable $e) {
            report($e);
            $this->deleteFiles($storedPaths);

            return back()->withInput()->with('error', 'Your application could not be submitted. Please try again.');
        }

        if (! $serviceRequest) {
            return $this->pendingBlocked();
        }

        $this->notifyAdmins($user, $serviceRequest);

        return redirect()
            ->route('citizen.applications')
            ->with('success', "Your request {$serviceRequest->reference_number} was submitted for barangay processing.");
    }

    // Applicant (from the verified profile)

    /** @return array{name:string,age:int,address:string,civil_status:?string,contact_number:?string,display:array}|null */
    private function applicant(User $user): ?array
    {
        $profile = $user->citizenProfile;

        if (! $profile || ! $profile->hasCompleteDetails() || $profile->age() === null) {
            return null;
        }

        $address = $profile->address ?: $profile->full_address;

        return [
            'name' => $user->name,
            'age' => $profile->age(),
            'address' => $address,
            'civil_status' => $profile->civil_status,
            'contact_number' => $user->phone,
            // Read-only summary shown on step 1: everything on the user and the profile, grouped
            'display' => $this->infoSections($user, $profile),
        ];
    }


    /**
     * Every visible column of the user and profile, grouped for display.
     * Hidden, internal and empty fields are skipped.
     *
     * @return array<string, array<string, string>> section title => label => value
     */
    private function infoSections(User $user, Model $profile): array
    {
        $skip = ['id', 'role', 'remember_token'];
        $sections = ['Personal information' => [], 'Contact information' => [], 'Address' => []];

        foreach ([$user, $profile] as $model) {
            foreach (array_keys($model->getAttributes()) as $key) {
                $value = $model->getAttribute($key);

                $internal = in_array($key, $skip, true)
                    || in_array($key, $model->getHidden(), true)
                    || preg_match('/(_id|_at|_path|_hash|_token)$/', $key);

                if ($internal || blank($value) || is_array($value) || is_object($value) && ! $value instanceof CarbonInterface) {
                    continue;
                }

                $text = match (true) {
                    $value instanceof CarbonInterface => $value->format('M j, Y'),
                    is_bool($value) => $value ? 'Yes' : 'No',
                    default => (string) $value,
                };

                $sections[$this->sectionFor($key)][Str::headline($key)] = $text;
            }
        }

        $sections['Personal information']['Age'] = (string) $profile->age();

        return array_filter($sections);
    }

    /** Which section a column belongs to, by its name. Anything else is personal. */
    private function sectionFor(string $key): string
    {
        return match (true) {
            (bool) preg_match('/phone|mobile|contact|email|tel/i', $key) => 'Contact information',
            (bool) preg_match('/address|street|barangay|purok|city|municipality|province|region|zip|postal/i', $key) => 'Address',
            default => 'Personal information',
        };
    }

    /** Step 2 fields with their defaults and old input already resolved. */
    private function formFields(array $service, string $userName): array
    {
        return array_map(function (array $field) use ($userName) {
            $key = $field['key'];
            $other = $field['other'] ?? false;

            return [
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => array_merge($field['options'] ?? [], $other ? ['Others'] : []),
                'required' => $field['required'] ?? true,
                'other' => $other,
                'wide' => $field['wide'] ?? false,
                'value' => old($key, ($field['prefill'] ?? null) === 'name' ? $userName : null),
                'otherValue' => old("{$key}_other"),
            ];
        }, $service['fields']);
    }

    /** After a failed submit, reopen on the step that has the first error. */
    private function startStep(array $errorKeys, array $service): int
    {
        if ($errorKeys === []) {
            return 1;
        }

        $fieldKeys = collect($service['fields'])->pluck('key')->flatMap(fn ($k) => [$k, "{$k}_other"])->all();

        foreach ($errorKeys as $key) {
            if (in_array($key, $fieldKeys, true)) {
                return 2;
            }
        }

        foreach ($errorKeys as $key) {
            if (str_starts_with($key, 'requirements')) {
                return 3;
            }
        }

        return 4;
    }

    // Validation

    private function service(string $slug): array
    {
        return config("barangay_services.services.{$slug}") ?? abort(404);
    }

    private function rules(array $service): array
    {
        $rules = ['agree_truth' => ['accepted']];

        foreach ($service['fields'] as $field) {
            $key = $field['key'];
            $hasOther = $field['other'] ?? false;
            $rule = [($field['required'] ?? true) ? 'required' : 'nullable'];

            switch ($field['type']) {
                case 'number':
                    array_push($rule, 'integer', 'min:0', 'max:1000000');
                    break;
                case 'tel':
                    array_push($rule, 'string', 'max:30');
                    break;
                case 'textarea':
                    array_push($rule, 'string', 'max:1000');
                    break;
                case 'select':
                    $options = $field['options'];
                    if ($hasOther) {
                        $options[] = 'Others';
                    }
                    $rule[] = Rule::in($options);
                    break;
                default:
                    array_push($rule, 'string', 'max:255');
            }

            $rules[$key] = $rule;

            // "Others" needs a short description
            if ($hasOther) {
                $rules["{$key}_other"] = ["required_if:{$key},Others", 'nullable', 'string', 'min:3', 'max:200'];
            }
        }

        foreach (array_keys($service['requirements']) as $key) {
            $rules["requirements.{$key}"] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.self::MAX_FILE_KB];
        }

        return $rules;
    }

    private function attributes(array $service): array
    {
        $attributes = [];

        foreach ($service['fields'] as $field) {
            $attributes[$field['key']] = strtolower($field['label']);
            $attributes["{$field['key']}_other"] = 'description';
        }

        return $attributes;
    }

    private function messages(array $service): array
    {
        $mb = self::MAX_FILE_KB / 1024;
        $messages = [
            'agree_truth.accepted' => 'Please confirm the information and acknowledge the privacy notice.',
            '*_other.required_if' => 'Please describe your answer.',
        ];

        foreach ($service['requirements'] as $key => $label) {
            $messages["requirements.{$key}.required"] = "Please upload: {$label}.";
            $messages["requirements.{$key}.mimes"] = "{$label} must be a JPG, PNG, or PDF file.";
            $messages["requirements.{$key}.max"] = "{$label} must not be larger than {$mb} MB.";
        }

        return $messages;
    }

    /** @return array{0: array<string,mixed>, 1: string} the saved details and the purpose text */
    private function resolveDetails(array $service, array $data): array
    {
        $details = [];

        foreach ($service['fields'] as $field) {
            $key = $field['key'];
            $value = $data[$key] ?? null;

            // Use the typed answer when "Others" was chosen
            if (($field['other'] ?? false) && $value === 'Others') {
                $value = trim($data["{$key}_other"] ?? '');
            }

            $details[$key] = is_string($value) ? trim($value) : $value;
        }

        // Fill {field_key} placeholders in the purpose template
        $purpose = preg_replace_callback(
            '/\{(\w+)\}/',
            fn ($m) => (string) ($details[$m[1]] ?? ''),
            $service['purpose_template']
        );

        return [$details, mb_substr(trim($purpose), 0, 500)];
    }

    // Helpers

    /** The user's open (pending or processing) requests for this service. */
    private function pendingQuery(User $user, array $service): Builder
    {
        return ServiceRequest::query()->whereBelongsTo($user)
            ->where('service_type', $service['type'])
            ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING]);
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                Storage::disk(self::DISK)->delete($path);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    // A failed notification must not break the submission
    private function notifyAdmins(User $user, ServiceRequest $serviceRequest): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CitizenSubmissionReceived(
                    'New permit request',
                    "{$user->name} submitted {$serviceRequest->reference_number} for review.",
                    route('admin.permits.show', $serviceRequest),
                ));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function pendingBlocked(): RedirectResponse
    {
        return redirect()
            ->route('citizen.applications')
            ->with('error', 'You already have an open request for this service.');
    }

    private function profileIncomplete(): RedirectResponse
    {
        return redirect()
            ->route('citizen.profile')
            ->with('error', 'Please complete your profile before applying for a service.');
    }
}
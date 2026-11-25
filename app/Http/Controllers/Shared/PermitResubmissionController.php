<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\PermitRevision;
use App\Models\ServiceRequest;
use App\Notifications\PermitCorrectionsRequested;
use App\Services\PermitCorrectionFields;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PermitResubmissionController extends Controller
{
    public function requestCorrections(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $revision = DB::transaction(function () use ($request, $serviceRequest) {
            $application = ServiceRequest::whereKey($serviceRequest)->lockForUpdate()->firstOrFail();
            if (! in_array($application->status, [ServiceRequest::PENDING, ServiceRequest::PROCESSING], true)) {
                throw ValidationException::withMessages(['flags' => 'This application is no longer open for review.']);
            }
            $fields = PermitCorrectionFields::for($application);
            $data = $request->validate([
                'flags' => ['required', 'array', 'list', 'min:1'],
                'flags.*' => ['required', 'string', 'distinct', Rule::in(array_keys($fields))],
                'message' => ['nullable', 'string', 'max:1000'],
            ], ['flags.required' => 'Select at least one item to correct.']);
            if (in_array('application_type', $data['flags'], true)) {
                $requiredKey = $fields['application_type']['value'] === 'Renewal' ? 'paid_up_capital' : 'file_old_clearance';
                if (blank($fields[$requiredKey]['value']) && ! in_array($requiredKey, $data['flags'], true)) {
                    throw ValidationException::withMessages(['flags' => 'Also flag '.$fields[$requiredKey]['label'].' and explain what the new application type requires.']);
                }
            }
            $items = [];
            foreach ($data['flags'] as $key) {
                $request->validate(["notes.$key" => ['required', 'string', 'max:500']], [], ["notes.$key" => $fields[$key]['label'].' note']);
                $items[$key] = [
                    'label' => $fields[$key]['label'], 'note' => $request->input("notes.$key"),
                    'old' => $fields[$key]['value'],
                ];
            }
            $revision = $application->revisions()->create([
                'requested_by' => $request->user()->id, 'reviewer_name' => $request->user()->name,
                'items' => $items, 'message' => $data['message'] ?? null,
            ]);
            $application->update(['status' => ServiceRequest::RESUBMISSION_REQUIRED]);

            return $revision;
        });

        try {
            $serviceRequest->user->notify(new PermitCorrectionsRequested($serviceRequest, $revision));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('admin.permits.show', $serviceRequest)->with('success', 'Resubmission requested.');
    }

    public function edit(Request $request, ServiceRequest $serviceRequest): View
    {
        $revision = $this->activeRevision($request, $serviceRequest);

        return view('citizen.applications.resubmit', [
            'application' => $serviceRequest, 'revision' => $revision, 'fields' => PermitCorrectionFields::for($serviceRequest),
        ]);
    }

    public function submit(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $created = [];
        $replaced = [];
        try {
            DB::transaction(function () use ($request, $serviceRequest, &$created, &$replaced) {
                $application = ServiceRequest::whereKey($serviceRequest)->lockForUpdate()->firstOrFail();
                $revision = $this->activeRevision($request, $application, true);
                $items = $revision->items;
                $fields = PermitCorrectionFields::for($application);
                $rules = ['corrections' => ['required', 'array:'.implode(',', array_keys($items))]];
                $labels = [];
                foreach ($items as $key => $item) {
                    $field = $fields[$key];
                    $presence = ($field['required'] ?? true) ? ['required'] : ['present', 'nullable'];
                    $rules["corrections.$key"] = [...$presence, ...$field['rules']];
                    $labels["corrections.$key"] = $item['label'];
                }
                $values = $request->validate($rules, [], $labels)['corrections'];
                $details = $application->details ?? [];
                foreach ($values as $key => $value) {
                    if ($fields[$key]['type'] === 'file') {
                        $items[$key] = $this->replaceDocument($application, $key, $value, $items[$key], $created, $replaced);
                        continue;
                    }
                    if ((string) $value === (string) $fields[$key]['value'] ||
                        ($fields[$key]['type'] === 'number' && is_numeric($fields[$key]['value']) && (float) $value === (float) $fields[$key]['value'])) {
                        throw ValidationException::withMessages(["corrections.$key" => 'Enter a value different from the previous answer.']);
                    }
                    if (in_array($key, ['applicant_name', 'age', 'civil_status', 'address'], true)) {
                        $application->setAttribute($key, $value);
                    } else {
                        $details[$key] = $value;
                    }
                    $items[$key]['new'] = $value;
                }
                $config = $application->serviceConfig();
                if (isset($values['application_type'])) {
                    $missing = match ($values['application_type']) {
                        'Renewal' => ! $application->documents()->where('requirement_key', 'old_clearance')->exists() ? 'Previous barangay business clearance' : null,
                        default => ! filled($details['paid_up_capital'] ?? null) ? 'Paid-up capital' : null,
                    };
                    if ($missing) {
                        throw ValidationException::withMessages(['corrections.application_type' => "$missing is required for this application type. Contact the reviewing office."]);
                    }
                }
                if (isset($values['purpose'])) {
                    $application->purpose = $values['purpose'];
                } elseif (isset($values['business_name']) || isset($values['application_type'])) {
                    $application->purpose = mb_substr(preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($details[$m[1]] ?? ''), $config['purpose_template']), 0, 500);
                }
                $application->update([
                    'details' => $details, 'contact_number' => $values['contact_number'] ?? $values['business_contact_number'] ?? $application->contact_number,
                    'status' => ServiceRequest::PENDING, 'processed_by' => null, 'processed_at' => null,
                ]);
                $revision->update(['items' => $items, 'submitted_at' => now()]);
            });
        } catch (Throwable $exception) {
            $this->removeFiles($created);
            if ($exception instanceof ValidationException || $exception instanceof HttpResponseException || $exception instanceof HttpExceptionInterface) {
                throw $exception;
            }
            report($exception);

            return back()->withInput()->withErrors(['corrections' => 'Your corrections could not be saved. Please select your files again and retry.']);
        }
        $this->removeFiles($replaced);

        return redirect()->route('citizen.applications.show', $serviceRequest)->with('success', 'Corrections submitted. Your application is pending review.');
    }

    private function activeRevision(Request $request, ServiceRequest $application, bool $submitting = false): PermitRevision
    {
        abort_unless($application->user_id === $request->user()->id, 404);
        $revision = $application->revisions()->whereNull('submitted_at')->latest('id')->first();
        if ($application->status !== ServiceRequest::RESUBMISSION_REQUIRED || ! $revision ||
            (($submitting || $request->has('revision')) && $request->integer('revision') !== $revision->id)) {
            throw new HttpResponseException(redirect()->route('citizen.applications.show', $application)
                ->with('warning', 'This correction link is no longer active. Check your application for the latest status.'));
        }

        return $revision;
    }

    private function replaceDocument(
        ServiceRequest $application,
        string $key,
        UploadedFile $value,
        array $item,
        array &$created,
        array &$replaced,
    ): array {
        $requirement = substr($key, 5);
        $document = $application->documents->firstWhere('requirement_key', $requirement);
        $disk = $document?->disk ?? 'supabase';
        $hash = hash_file('sha256', $value->getRealPath());
        if ($document && Storage::disk($disk)->exists($document->path)) {
            $stream = Storage::disk($disk)->readStream($document->path);
            if (! $stream) {
                throw new RuntimeException('Could not read the previous document.');
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            $item['old_hash'] = hash_final($context);
            if ($item['old_hash'] === $hash) {
                throw ValidationException::withMessages(["corrections.$key" => 'Choose a different file from the previous upload.']);
            }
            $replaced[] = [$disk, $document->path];
        }
        $path = $value->store("service-requirements/{$application->id}", $disk);
        if (! $path) {
            throw new RuntimeException('Could not store the replacement document.');
        }
        $created[] = [$disk, $path];
        $application->documents()->updateOrCreate(['requirement_key' => $requirement], [
            'label' => $item['label'], 'disk' => $disk, 'path' => $path,
            'original_name' => $value->getClientOriginalName(), 'mime_type' => $value->getMimeType(),
            'size' => $value->getSize(),
        ]);
        $item['new'] = $value->getClientOriginalName();
        $item['new_hash'] = $hash;

        return $item;
    }

    private function removeFiles(array $files): void
    {
        foreach ($files as [$disk, $path]) {
            try {
                Storage::disk($disk)->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}

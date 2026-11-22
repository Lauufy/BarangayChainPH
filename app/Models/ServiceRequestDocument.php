<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A supporting document a citizen uploaded for a service request. */
#[Fillable([
    'service_request_id',
    'requirement_key',
    'label',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
])]
class ServiceRequestDocument extends Model
{
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['first_name', 'last_name', 'email', 'service', 'subject', 'body', 'status'])]
class ContactMessage extends Model
{
    public const NEW = 'new';

    public const REPLIED = 'replied';

    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A message sent from the public Contact page. */
class ContactMessage extends Model
{
    use Audited;
    use SoftDeletes;

    public const STATUSES = ['new', 'read', 'replied'];

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'subject', 'message', 'status'];
}

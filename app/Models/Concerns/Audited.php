<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Records who created / changed / deleted / restored a record, with the old and new values. */
trait Audited
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logAll()
            ->logExcept(array_merge($this->auditExcept ?? [], ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'updated_at', 'created_at', 'deleted_at', 'last_seen_at']))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyEntry extends Model
{
    protected $table = 'loyalty_ledger';

    protected $guarded = ['id'];
}

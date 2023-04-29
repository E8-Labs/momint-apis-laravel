<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountStatus extends Model
{
    use HasFactory;
    const StatusPending = 1;
    const StatusActive = 2;
    const StatusDisabled = 3;
    const StatusDeleted = 4;
}

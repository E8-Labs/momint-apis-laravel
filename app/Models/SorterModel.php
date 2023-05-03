<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SorterModel extends Model
{
    use HasFactory;
    const DescendingAlphabetically = 1;
    const NewAccounts = 2;
    const MonstMints = 3;
}

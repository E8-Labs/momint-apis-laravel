<?php

namespace App\Models\Minting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MintableListingStatus extends Model
{
    use HasFactory;
    const StatusMinted = 1;
    const StatusListed = 2;
    const StatusBoth = 3;
    const StatusDraft = 4;
}

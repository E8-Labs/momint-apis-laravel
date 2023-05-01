<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    use HasFactory;
    const NewMessage = 1;
	const NewUser = 2;
	const NewNFT = 3;
	const NewEmail = 4;
	const FlaggedUser = 5;
	const FlaggedNFT = 6;
	const NewFeedback = 7;
    
    
    
    
    
    
    
    
}

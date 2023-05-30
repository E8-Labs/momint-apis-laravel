<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\Minting\MintableListing;
use App\Models\Minting\MintableListingStatus;

class UserProfileLiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = User::where('id', $this->user_id)->first();
        // $url = $this->image_url;
        $p = $user->provider_name;
        if($p === NULL){
            $p = "email";
        }
        $count = Notification::where('to_user', $user->id)->where('is_read', 0)->count('id');

        $listings = MintableListing::where('user_id', $user->id)->where('minting_status', '!=', MintableListingStatus::StatusDraft)->count('id');
        $username = $this->username;
        if(strpos($username, "@") === 0){
            
        }
        else{
            $username = "@" . $username; //str_replace($username, '', "@") ;
        }


        return [
            "id" => $this->user_id,
            "email" => $user->email,
            "name" => $this->name,
            "bio" => $this->bio,
            "username" => $username,
            "profile_image" => \Config::get('constants.profile_images').$this->image_url,
             "user_id" => $user->id,
            "role" => $user->role,
            "listings_count" => $listings,

            "unread_notifications" => $count,
            "updated_at" => $this->updated_at, // can be used to check last online
            // "unread_messages" => $unread_messages,

        ];
    }
}

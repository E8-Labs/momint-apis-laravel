<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class UserProfileLiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::where('id', $this->user_id)->first();
        // $url = $this->image_url;
        $p = $user->provider_name;
        if($p === NULL){
            $p = "email";
        }
        return [
            "id" => $this->user_id,
            "email" => $user->email,
            "name" => $this->username,
            "profile_image" => \Config::get('constants.profile_images').$this->image_url,
            "authProvider" => $p,
            'city' => $this->city,
            "state" => $this->state,
            'lat' => $this->lat,
            'lang' => $this->lang,
             "user_id" => $user->id,
             'nationality' => $this->nationality,
            
            

            // "unread_notifications" => $count,
            // "unread_messages" => $unread_messages,

        ];
    }
}

<?php

namespace App\Http\Resources\Minting;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Minting\MintListingResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Minting\MintableListing;

class FlaggedListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = Profile::where('user_id', $this->from_user)->first();
        $listing = MintableListing::where('id', $this->listing_id)->first();


        return[
            'id' => $this->id,
            "from_user" => new UserProfileLiteResource($user),
            "listing" => new MintListingResource($listing),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}

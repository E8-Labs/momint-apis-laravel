<?php

namespace App\Http\Resources\Minting;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Minting\MintableListingImages;
use App\Models\Minting\MintableListingTags;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Minting\MintableListingImageResource;

class MintListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $profile = Profile::where('user_id', $this->user_id)->first();

        $images = MintableListingImages::where('listing_id', $this->id)->get();
        $tags = MintableListingTags::where('listing_id', $this->id)->get();
        return [
            "id" => $this->id,
            "listing_name" => $this->listing_name,
            "listing_description" => $this->listing_description,
            "is_explicit_content" => (bool)$this->is_explicit_content,
            "listing_price" => $this->listing_price,
            "currency"=> $this->currency,
            "royalty_percentage"=> $this->royalty_percentage,
            // "profile_image" => \Config::get('constants.profile_images').$this->image_url,
            "user" => new UserProfileLiteResource($profile),
            'images' => MintableListingImageResource::collection($images),
            "tags" => $tags,
            'minting_status' => $this->minting_status,
            "created_at" => $this->created_at,
        ];
    }
}

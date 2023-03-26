<?php

namespace App\Http\Resources\Minting;

use Illuminate\Http\Resources\Json\JsonResource;

class MintableListingImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $size = (($this->image_width * $this->image_height) / 8 ) / 1024; //kb
        $sizeUnit = "Kb";
        if($size >= 1024){
            $size = $size / 1024; //Mb
            $sizeUnit = "Mb";
        }
        $size = number_format((float)$size, 2, '.', '');
        return [
            "id" => $this->id,
            "ipfs_hash" => $this->ipfs_hash,
            "image_location" => $this->image_location,
            "image_width" => $this->image_width,
            "image_url" => \Config::get('constants.listing_images').$this->image_url,
            "image_height" => $this->image_height,
            
            'lat' => $this->lat,
            'lang' => $this->lang,
            "size" => $size . ' ' . $sizeUnit
        ];
    }
}

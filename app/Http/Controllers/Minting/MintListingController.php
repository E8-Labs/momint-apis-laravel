<?php

namespace App\Http\Controllers\Minting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Auth\VerificationCode;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use App\Http\Resources\Minting\MintListingResource;

use Illuminate\Support\Facades\Http;

use App\Models\Minting\MintableListing;
use App\Models\Minting\MintableListingImages;
use App\Models\Minting\MintableListingTags;
use App\Models\Minting\MintableListingStatus;

class MintListingController extends Controller
{
    function addListing(Request $request){
    	$validator = Validator::make($request->all(), [
			'listing_name' => 'required|string|max:255',
            'listing_description' => 'required',
            'images' => 'required',
            'tags' => 'required'
				]);

			if($validator->fails()){
				return response()->json(['status' => false,
					'message'=> 'validation error',
					'data' => null, 
					'validation_errors'=> $validator->errors()]);
			}

			DB::beginTransaction();
			$listing = new MintableListing;
			$listing->listing_name = $request->listing_name;
			$listing->is_explicit_content = $request->is_explicit_content;
			$listing->listing_description = $request->listing_description;
			$saved = $listing->saved();

			if(!$saved){
				return response()->json(['status' => false,
					'message'=> 'Error saving Listing',
					'data' => null, ]);
			}

			//save tags 
			$tags = $listing->tags;

			foreach($tags as $tag){
				$mintTag = new MintableListingTags;
				$mintTag->tag = $tag;
				$mintTag->listing_id = $listing->id;
				$mintTag->save();

			}


			//save images
			$images = $request->images; //array of base64 images alongwith data

			foreach($images as $image){
				$b64 = $image->base64;

			}
    }

    function storeBase64Image($ima, User $user){
    	$fileName =  rand(). date("h:i:s").'image.png';
    		$folder = 'momint';
    		$ima = trim($ima);
    		$ima = str_replace('data:image/png;base64,', '', $ima);
    		$ima = str_replace('data:image/jpg;base64,', '', $ima);
    		$ima = str_replace('data:image/jpeg;base64,', '', $ima);
    		$ima = str_replace('data:image/gif;base64,', '', $ima);
    		$ima = str_replace(' ', '+', $ima);
		
    		$imageData = base64_decode($ima);
    		//Set image whole path here 
    		$filePath = $_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Images/" . $user->id . '/' . $fileName;

            if(!Storage::exists($_SERVER['DOCUMENT_ROOT']."/" . $folder ."/storage/app/Images/" . $user->id )){
                Storage::makeDirectory($_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Images/" . $user->id );
            }
   			file_put_contents($filePath, $imageData);
   			$url = "Images/" . $user->id . '/' . $fileName;
   			return $url;
    }

    function updateListing(Request $request){

    }
}

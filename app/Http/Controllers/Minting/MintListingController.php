<?php

namespace App\Http\Controllers\Minting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
			$listing->user_id = Auth::user()->id;
			$saved = $listing->save();

			if(!$saved){
				DB::rollBack();
				return response()->json(['status' => false,
					'message'=> 'Error saving Listing',
					'data' => null, ]);
			}

			//save tags 
			$tags = $request->tags;

			foreach($tags as $tag){
				$mintTag = new MintableListingTags;
				$mintTag->tag = $tag;
				$mintTag->listing_id = $listing->id;
				$mintTag->save();

			}


			//save images
			$images = $request->images; //array of base64 images alongwith data

			foreach($images as $image){
				$b64 = $image["base64"];
				$url = $this->storeBase64Image($b64, Auth::user());

				$mintImage = new MintableListingImages;
				$mintImage->listing_id = $listing->id;
				$mintImage->image_url = $url;
				$mintImage->ipfs_hash = $image["ipfs_hash"];
				$mintImage->image_location = $image["image_location"];
				$mintImage->image_width = $image["image_width"];
				$mintImage->image_height = $image["image_height"];
				$mintImage->lat = $image["lat"];
				$mintImage->lang = $image["lang"];
				$mintImage->image_count = $image["image_count"];
				$mintImage->save();

			}

			DB::commit();

			return response()->json(['status' => true,
					'message'=> 'Listing saved',
					'data' => new MintListingResource($listing), 
				]);
    }

    function updateListing(Request $request){
    	$user = Auth::user();

    	$listing_id = $request->listing_id;
    	$listing = MintableListing::where('id', $listing_id)->first();


    	$updateArray = array();
    	if($request->has('listing_price')){
    		$listing->listing_price = $request->listing_price;
    	}
    	if($request->has('currency')){
    		$listing->currency = $request->currency;
    	}
    	if($request->has('royalty_percentage')){
    		$listing->royalty_percentage = $request->royalty_percentage;
    	}
    	$saved = $listing->save();
    	if($saved){
				return response()->json(['status' => true,
					'message'=> 'Listing saved',
					'data' => new MintListingResource($listing), 
				]);
    	}
    	else{
    		return response()->json(['status' => false,
					'message'=> 'Listing not saved',
					'data' => new MintListingResource($listing), 
				]);
    	}

    }

    function getListings(Request $request){
    	$user = Auth::user();
    	$userid = $user->id;
    	if($request->has('user_id')){
    		$userid = $request->user_id;
    	}
    	$off_set = 0;
    	if($request->has('off_set')){
    		$off_set = $request->off_set;
    	}
    	$status = MintableListingStatus::StatusBoth;
    	$list = MintableListing::where('user_id', $userid)->skip($off_set)->take(20)->get();
    	if($request->has('status')){
    		$status = $request->status;
    		$list = MintableListing::where('user_id', $userid)->where('minting_status', $status)->skip($off_set)->take(20)->get();
    	}

    	


    	return response()->json(['status' => true,
					'message'=> 'Listings',
					'data' => MintListingResource::collection($list), 
				]);

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
    		$filePath = $_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Images/" . $fileName;

            if(!Storage::exists($_SERVER['DOCUMENT_ROOT']."/" . $folder ."/storage/app/Images/" )){
            	// echo "doesn't exist";
                Storage::makeDirectory($_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Images/"  );
            }
   			file_put_contents($filePath, $imageData);
   			$url = "Images/" . $fileName;
   			return $url;
    }

    
}

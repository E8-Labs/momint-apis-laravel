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
use App\Models\Auth\UserRole;
use App\Models\Auth\VerificationCode;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use App\Http\Resources\Minting\MintListingResource;
use App\Http\Resources\Minting\FlaggedListingResource;

use Illuminate\Support\Facades\Http;

use App\Models\Minting\MintableListing;
use App\Models\Minting\MintableListingImages;
use App\Models\Minting\MintableListingTags;
use App\Models\Minting\MintableListingStatus;

use App\Models\Minting\FlaggedListing;

use App\Models\Notification;
use App\Models\NotificationType;

class MintListingController extends Controller
{
    function addListing(Request $request){
        $validator = Validator::make($request->all(), [
            'listing_name' => 'required|string|max:255',
            'listing_description' => 'required',
            'images' => 'required',
            // 'tags' => 'required'
                ]);

            if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }
            $user = Auth::user();

            if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
            }
            $profile = Profile::where('user_id', $user->id)->first();
            $profile->action = "AddListing";
            $profile->save();
            \Log::info("1");

            DB::beginTransaction();
            $listing = new MintableListing;
            $listing->listing_name = $request->listing_name;
            $listing->is_explicit_content = $request->is_explicit_content;
            $listing->listing_description = $request->listing_description;
            if($request->has('status')){
                $listing->minting_status = $request->status;
            }
            $listing->user_id = Auth::user()->id;
            $saved = $listing->save();
\Log::info("2");
            if(!$saved){
                DB::rollBack();
                return response()->json(['status' => false,
                    'message'=> 'Error saving Listing',
                    'data' => null, ]);
            }

            //save tags 
            $tags = $request->tags;
\Log::info("3");
            foreach($tags as $tag){
                \Log::info("Saving ". $tag);
                $mintTag = new MintableListingTags;
                $mintTag->tag = $tag;
                $mintTag->listing_id = $listing->id;
                $mintTag->save();

            }

\Log::info("4");
            //save images
            $images = $request->images; //array of base64 images alongwith data

            foreach($images as $image){
                $b64 = $image["base64"];
                $url = $this->storeBase64Image($b64, Auth::user());

                $mintImage = new MintableListingImages;
                $mintImage->listing_id = $listing->id;
                $mintImage->image_url = $url;
                $ipfs_hash = $image["ipfs_hash"];
                if($ipfs_hash == NULL){
                    $ipfs_hash = "";
                }
                $mintImage->ipfs_hash = $ipfs_hash;
                $loc = $image["image_location"];
                if($loc === NULL){
                    $loc = "";
                }
                $mintImage->image_location = $loc;
                $mintImage->image_width = $image["image_width"];
                $mintImage->image_height = $image["image_height"];
                $mintImage->lat = $image["lat"];
                $mintImage->lang = $image["lang"];
                $mintImage->image_count = $image["image_count"];
                $mintImage->save();

            }
\Log::info("5");
            DB::commit();
            $admin = User::where('role', UserRole::Admin)->first();
            Notification::add(NotificationType::NewNFT, $user->id, $admin->id, $listing);
            return response()->json(['status' => true,
                    'message'=> 'Listing saved',
                    'data' => new MintListingResource($listing), 
                ]);
    }

    function updateListing(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }

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
        if($request->has('minting_status')){
            $listing->minting_status = $request->minting_status;
            // if($request->minting_status == MintableListingStatus::StatusDraft){
            //     $listing->minting_status = MintableListingStatus::StatusMinted;
            // }
        }

        if($request->has('listing_name')){
            $listing->listing_name = $request->listing_name;
        }
        if($request->has('is_explicit_content')){
            $listing->is_explicit_content = $request->is_explicit_content;
        }
        if($request->has('listing_description')){
            $listing->listing_description = $request->listing_description;
        }
        
        

        //set IPFS Hashes if it is a drafted listing
        if($request->has("images")){
            $images = $request->images; //array of base64 images alongwith data

            foreach($images as $image){
                
                $image_id = $image["image_id"];
                $mintImage = MintableListingImages::where('id', $image_id)->first();
                // $mintImage->listing_id = $listing->id;
                // $mintImage->image_url = $url;
                $ipfs_hash = $image["ipfs_hash"];
                if($ipfs_hash == NULL){
                    $ipfs_hash = "";
                }
                $mintImage->ipfs_hash = $ipfs_hash;
                $mintImage->save();

            }
        }




        $saved = $listing->save();
        if($saved){
            $profile = Profile::where('user_id', $user->id)->first();
            $profile->action = "Update";
            $profile->save();
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

    function deleteListing(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        $profile = Profile::where('user_id', $user->id)->first();
        $profile->action = "DeleteListing";
        $profile->save();
        if($request->has('listing_id')){
            $listing = MintableListing::where('id', $request->listing_id)->first();
            if(!$listing){
                return response()->json(['status' => false,
                    'message'=> 'No Such Listing',
                    'data' => null, 
                ]);
            }

            MintableListing::where('id', $request->listing_id)->delete();

            return response()->json(['status' => true,
                    'message'=> 'Listing deleted',
                    'data' => null, 
                ]);

        }
        else{
            return response()->json(['status' => false,
                    'message'=> 'Listing id not present',
                    'data' => null, 
                ]);
        }
    }

    function flagListing(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        if($request->has('listing_id')){
            $listing = FlaggedListing::where('listing_id', $request->listing_id)->where('from_user', $user->id)->first();
            if($listing){
                return response()->json(['status' => false,
                    'message'=> 'Already flagged',
                    'data' => null, 
                ]);
            }

            $flag = new FlaggedListing;
            $flag->from_user = $user->id;
            $flag->listing_id = $request->listing_id;
            $saved = $flag->save();
            if ($saved){
                $admin = User::where('role', UserRole::Admin)->first();

                Notification::add(NotificationType::FlaggedNFT, $user->id, $admin->id, $flag);
                return response()->json(['status' => true,
                    'message'=> 'Listing flagged',
                    'data' => new FlaggedListingResource($flag), 
                ]);
            }
            else{
                return response()->json(['status' => false,
                    'message'=> 'Listing not flagged',
                    'data' => null, 
                ]);
            }

            

        }
        else{
            return response()->json(['status' => false,
                    'message'=> 'Listing id not present',
                    'data' => null, 
                ]);
        }
    }

    function getPreviouslyUsedTags(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        $profile = Profile::where('user_id', $user->id)->first();
        $profile->action = "RecentTags";
        $profile->save();
        $userid = $user->id;
        $listing_ids = MintableListing::where('user_id', $user->id)->pluck('id')->toArray();
        $list = MintableListingTags::whereIn('listing_id', $listing_ids)->groupBy('tag')->get();
        return response()->json(['status' => true,
                    'message'=> 'Recent Tags',
                    'data' => $list, 
                ]);
    }

    function getListings(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        $userid = $user->id;
        $profile = Profile::where('user_id', $user->id)->first();
        $profile->action = "LoadListings";
        $profile->save();
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

        if($user->role === UserRole::Admin){
            $userIds = Profile::where('account_status', AccountStatus::StatusDisabled)
            ->orWhere('account_status', AccountStatus::StatusDeleted)->pluck('user_id')->toArray();
            $list = MintableListing::whereNotIn('user_id', $userIds)->orderBy("created_at", "DESC")->skip($off_set)->take(20)->get();
            if($request->has('user_id')){
                $list = MintableListing::where('user_id', $userid)->orderBy("created_at", "DESC")->skip($off_set)->take(20)->get();
            }   
        }

        


        return response()->json(['status' => true,
                    'message'=> 'Listings',
                    'data' => MintListingResource::collection($list), 
                ]);

    }


    function getFlaggedListings(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        $userid = $user->id;
        
        if($request->has('off_set')){
            $off_set = $request->off_set;
        }
        
        $list = FlaggedListing::skip($off_set)->take(20)->get();
        


        return response()->json(['status' => true,
                    'message'=> 'Flagged Listings',
                    'data' => FlaggedListingResource::collection($list), 
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
            $filePath = $_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Listing/" . $fileName;

            if(!Storage::exists($_SERVER['DOCUMENT_ROOT']."/" . $folder ."/storage/app/Listing/" )){
                // echo "doesn't exist";
                Storage::makeDirectory($_SERVER['DOCUMENT_ROOT']."/". $folder ."/storage/app/Listing/"  );
            }
            file_put_contents($filePath, $imageData);
            $url = "Listing/" . $fileName;
            return $url;
    }

    
}

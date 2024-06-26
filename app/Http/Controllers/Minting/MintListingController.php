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
use App\Models\Auth\AccountStatus;

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
            
            if($request->has('listing_id')){
                // if there is already a listing id then a draft is being minted and saved
                // delete the already present id 
                // set the status to Minted instead of draft
                // Then save the listing brand new

                // $del = MintableListing::where('id', $request->listing_id)->delete();
                //New logic: Instead of deleting, just get that listing and change the details
                $listing = MintableListing::where('id', $request->listing_id)->first();
                if($listing->minting_status === MintableListingStatus::StatusDraft){
                    $listing->minting_status = MintableListingStatus::StatusMinted;
                }
                else{
                    $listing->minting_status = MintableListingStatus::StatusListed;
                }

            }
            else{
                $listing = new MintableListing;
            }
            $listing->listing_name = $request->listing_name;
            $listing->is_explicit_content = $request->is_explicit_content;
            $listing->listing_description = $request->listing_description;
            $listing->nft_id = $request->nft_id;
            if($request->has('minting_status')){ // if draft then save it as a draft.
                $listing->minting_status = $request->minting_status;
            }
            
            if($request->has('listing_price')){
                $listing->minting_status = MintableListingStatus::StatusListed;
                $listing->listing_price = $request->listing_price;
            }
            if($request->has('currency')){
                $listing->minting_status = MintableListingStatus::StatusListed;
                $listing->currency = $request->currency;
            }
            if($request->has('royalty_percentage')){
                $listing->minting_status = MintableListingStatus::StatusListed;
                $listing->royalty_percentage = $request->royalty_percentage;
            }
            if($request->has('gas_fee')){
                $listing->minting_status = MintableListingStatus::StatusListed;
                $listing->gas_fee = $request->gas_fee;
            }
            if($request->has('transaction_detail')){
                $tr = $request->transaction_detail;
                $hash = $tr["blockHash"];
                $listing->transaction_hash = $hash;
                $listing->minting_status = MintableListingStatus::StatusListed;
                $listing->gas_fee = $request->gas_fee;
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
            if($request->has('listing_id')){
                MintableListingTags::where('listing_id', $request->listing_id)->delete();
            }
            foreach($tags as $tag){
                \Log::info("Saving ". $tag);
                $mintTag = new MintableListingTags;
                $mintTag->tag = $tag;
                $mintTag->listing_id = $listing->id;
                $mintTag->save();

            }

\Log::info("4");
            //save images
            if($request->has('listing_id')){ // no need to add the images again
                
            }
            else{
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
            }
            
\Log::info("5");
            DB::commit();
            if($listing->minting_status != MintableListingStatus::StatusDraft && $listing->minting_status != MintableListingStatus::StatusListed){
                $admin = User::where('role', UserRole::Admin)->first();
                Notification::add(NotificationType::NewNFT, $user->id, $admin->id, $listing);
            }
            
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
        $list = MintableListingTags::whereIn('listing_id', $listing_ids)->get();
        $tags = $this->unique_array($list, "tag");
        return response()->json(['status' => true,
                    'message'=> 'Recent Tags',
                    'data' => $tags, 
                ]);
    }
    
    function unique_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();
    
        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[] = $val;
            }
            $i++;
        }
        return $temp_array;
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
            $list = MintableListing::whereNotIn('user_id', $userIds)->where('minting_status', '!=', MintableListingStatus::StatusDraft)->orderBy("created_at", "DESC")->skip($off_set)->take(20)->get();
            $search = $request->search;
                if($search != ''){
                    $tokens = explode(" ", $search);
                    // return $tokens;
                    $query = MintableListing::whereNotIn('user_id', $userIds)->where('minting_status', '!=', MintableListingStatus::StatusDraft)->orderBy("created_at", "DESC");
                    
                    $query->where(function($query) use($tokens){
                        foreach($tokens as $tok){
                            $ids = MintableListingTags::where('tag', 'LIKE', "%$tok%")->pluck('listing_id')->toArray();
                            $query->where('listing_name', 'LIKE', "%$tok%")->orWhere('listing_description', 'LIKE', "%$tok%")->orWhereIn('id', $ids);
                        }
                    });
                    $list = $query->orderBy("created_at", "DESC")->skip($off_set)->take(20)->get();
                }
            if($request->has('user_id')){
                $list = MintableListing::where('user_id', $userid)->where('minting_status', '!=', MintableListingStatus::StatusDraft)->orderBy("created_at", "DESC")->skip($off_set)->take(20)->get();
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
        
        $list = FlaggedListing::skip($off_set)->orderBy("created_at", "DESC")->take(20)->get();
        


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

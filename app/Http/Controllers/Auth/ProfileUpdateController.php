<?php

namespace App\Http\Controllers\Auth;

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
// use App\Http\Resources\Profile\UserProfileLiteResource;
use Illuminate\Support\Facades\Http;

class ProfileUpdateController extends Controller
{
    public function updateProfile(Request $request){
    	$user = Auth::user();
    	$profile = Profile::where('user_id', $user->id)->first();
        $profile->action = "Update";
        if($request->has('action')){
            $profile->action = $request->action;
        }
    	if($request->has('fcm_token')){
    		$profile->fcm_token = $request->fcm_token;
    	}
    	if($request->has('name')){
    		$profile->name = $request->name;
    	}
        if($request->has('bio')){
            $profile->bio = $request->bio;
        }
    	if($request->has('username')){
    		$profile->username = $request->username;
    	}
    	if($request->has('city')){
    		$profile->city = $request->city;
    	}
    	if($request->has('state')){
    		$profile->state = $request->state;
    	}
    	if($request->has('lat')){
    		$profile->lat = $request->lat;
    	}
    	if($request->has('lang')){
    		$profile->lang = $request->lang;
    	}
        if($request->hasFile('profile_image'))
        {
            $data=$request->file('profile_image')->store('Images/');
            $profile->image_url = $data;
            
        }

    	$saved = $profile->save();
    	if($saved){
    		return response()->json([
                'status' => true,
                'message' => 'Profile udpated',
                'data' => new UserProfileFullResource($profile),
            ], 200);
    	}
    	else{
    		return response()->json([
                'status' => false,
                'message' => 'User not updated',
            ], 200);
    	}

    }
}

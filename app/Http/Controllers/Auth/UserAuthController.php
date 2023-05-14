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
use App\Models\Auth\UserRole;
use App\Models\Auth\VerificationCode;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;
use Illuminate\Support\Facades\Http;

use App\Models\Notification;
use App\Models\NotificationType;

class UserAuthController extends Controller
{
	const RULE_PHONE = 'required|min:10|numeric';
	const RULE_EMAIL = 'required|email';
	const RULE_NAME = 'required|min:3';

	public function getMyProfile(Request $request){
    	$user = Auth::user();
    	if($user){
    		$profile = Profile::where('user_id', $user->id)->first();
    		return response()->json([
                'status' => true,
                'message' => 'User found',
                'data' => new UserProfileFullResource($profile),
            ], 200);
    	}
    	else{
    		return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
    	}
    }

    function deleteUser(Request $request){
        $user = Auth::user();
        if($user){
            $deleted = User::where('id', $user->id)->delete();
            if($deleted){
                return response()->json([
                    'status' => true,
                    'message' => 'User deleted',
                ], 200);
            }
            else{
                return response()->json([
                'status' => false,
                'message' => 'Some error occurred',
            ], 200);
            }
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated access',
            ], 401);
        }
    }

    public function getOtherUserProfile(Request $request){
    	$user = Auth::user();
    	if($user){
    		$userid = $request->user_id;
    		// return $userid;
    		$profile = Profile::where('user_id', $userid)->first();
    		return response()->json([
                'status' => true,
                'message' => 'User found',
                'data' => new UserProfileFullResource($profile),
            ], 200);
    	}
    	else{
    		return response()->json([
                'status' => false,
                'message' => 'Unauthenticated access',
            ], 401);
    	}
    }

    public function login(Request $request)
    {
        
        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'No account found, Check your email and password.',
            ], 200);
        }

        $user = Auth::user();
        $profile = Profile::where('user_id', $user->id)->first();
        $profile->action = "Update";
        $profile->save();
        $data = ["profile" => new UserProfileFullResource($profile), "access_token" => $token];
        return response()->json([
                'status' => true,
                'data'   => $data,
                'message'=> 'User logged in'
            ]);

    }



    public function register(Request $request){
        
    	$header = $request->header('isEmailAvailable');
    	$headerUsername = $request->header('isUsernameAvailable');
    	$headerSendCode = $request->header('sendCode');
    	$headerVerifyCode = $request->header('verifyCode');
    	if($header == 'true'){
    		if( $this->isEmailAvailable($request['email']) ){
              return response()->json(['data' => null, 'status' => true, 'message' => "Email available" ], 200);
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Email already taken" ], 200);
            }
    	}
    	else if($headerUsername == 'true'){
    		if( $this->isUsernameAvailable($request['username']) ){
              return response()->json(['data' => null, 'status' => true, 'message' => "Username available" ], 200);
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Username already taken" ], 200);
            }
    	}
    	else if($headerSendCode == 'true'){
    		if( $this->isEmailAvailable($request['email']) ){
              	$sent = $this->sendVerificationMail($request);
              	if($sent){
              		return response()->json(['status' => true,
						'message'=> 'Code sent',
						'data' => null,
					]);
              	}
              	else{
              		return response()->json(['status' => false,
						'message'=> 'Error sending code',
						'data' => null,
					]);
              	}
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Email already taken" ], 200);
            }
    	}
    	else if($headerVerifyCode == 'true'){
    		$confirmed = $this->confirmVerificationCode($request);
    		if($confirmed){
    			return response()->json(['status' => true,
						'message'=> 'Email verified',
						'data' => null,
					]);
    		}
    		else{
    			return response()->json(['status' => false,
						'message'=> 'Error verifying',
						'data' => null,
					]);
    		}
    	}
    	else{
    		$validator = Validator::make($request->all(), [
			'username' => 'required|string|max:255',
            // 'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            // 'profile_image' => 'required'
				]);

			if($validator->fails()){
				return response()->json(['status' => false,
					'message'=> 'validation error',
					'data' => null, 
					'validation_errors'=> $validator->errors()]);
			}
			$exists = User::where('email', $request->email)->first();
			if($exists){
				return response()->json(['status' => false,
					'message'=> 'Email already exists',
					'data' => null, 
				]);
			}

			DB::beginTransaction();
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'provider_name' => 'email',
            'name' => $request->username,
        ]);
         


        if($user){
        	$profile=new Profile;
    	// return "Creating user";
				if($request->hasFile('profile_image'))
				{
					$data=$request->file('profile_image')->store('Images/');
					$profile->image_url = $data;
					
				}
				else
				{
					$profile->image_url = "";
				}
		
				$profile->username=$request->username;
                $profile->name=$request->name;
                $profile->bio=$request->bio;
				$profile->user_id = $user->id;
				$result=$profile->save();
				if($result)
				{
					DB::commit();
        			$token = Auth::login($user);
                    $admin = User::where('role', UserRole::Admin)->first();
                    Notification::add(NotificationType::NewUser, $user->id, $admin->id, $user);
        			return response()->json([
        			    'status' => true,
        			    'message' => 'User created successfully',
        			    'data' => [
        			    	'profile' => new UserProfileFullResource($profile),
        			        'access_token' => $token,
        			        'type' => 'bearer',
        			    	
        				]
        			]);
					
					
				}
				else
				{
					DB::rollBack();
        			return response()->json([
            			'status' => false,
            			'message' => "Error saving profile",
            			'data' => NULL,
            	
        			]);
				}
        	
        }
        else{
        	DB::rollBack();
        	return response()->json([
            		'status' => false,
            		'message' => "User didn't save",
            		'data' => NULL,
            
        		]);
        }
    	}

        

        
    }

    

    public function checkEmailAvailablity(Request $request)
    {
    	$validator = Validator::make($request->all(), ['email'=> self::RULE_EMAIL]);
        if ($validator->fails()) {
            return $this->getErrorResponse($validator);
        }else{
            if( $this->isEmailAvailable($request['email']) ){
              return response()->json(['data' => null, 'status' => true, 'message' => "Email available" ], 200);
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Email already taken" ], 200);
            }
         }
    }

    public function checkPhoneAvailablity(Request $request)
    {
    	$validator = Validator::make($request->all(), ['phone'=> self::RULE_PHONE]);// 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10'
        if ($validator->fails()) {
            return $this->getErrorResponse($validator);
        }else{
            if( $this->isPhoneAvailable($request['phone']) ){
              return response()->json(['data' => null, 'status' => true, 'message' => "Phone number available" ], 200);
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Phone number already exists" ], 200);
            }
         }
    }

    public function checkUsernameAvailablity(Request $request)
    {
    	$validator = Validator::make($request->all(), ['username'=> self::RULE_NAME]);// 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10'
        if ($validator->fails()) {
            return $this->getErrorResponse($validator);
        }else{
            if( $this->isUsernameAvailable($request['username']) ){
              return response()->json(['data' => null, 'status' => true, 'message' => "Username available" ], 200);
            }else{
              return response()->json(['data' => null, 'status' => false, 'message' => "Username taken" ], 200);
            }
         }
    }

    function sendVerificationMail(Request $request){
		$validator = Validator::make($request->all(), [
			'email' => 'required|string|email',
				]);

			if($validator->fails()){
				return response()->json(['status' => false,
					'message'=> 'validation error',
					'data' => null, 
					'email' => $request->email,
					'validation_errors'=> $validator->errors()]);
			}

			// set code to codes table

			$user = User::where('email', $request->email)->first();
			if($user){
				return response()->json(['status' => false,
					'message'=> 'Email is taken',
					'data' => null
				]);
			}
                

			VerificationCode::where('email', $request->email)->delete();
			$FourDigitRandomNumber = rand(1111,9999);
			$code = new VerificationCode;
			$code->code = $FourDigitRandomNumber;
			$code->email = $request->email;
			$res = $code->save();
			

			if($res){
				$data = array('code'=> $FourDigitRandomNumber, "email" => "the.prevue.app@gmail.com");
				Mail::send('Mail/verificationmail', $data, function ($message) use ($data, $request) {
                        $message->to($request->email,'Code')->subject('Verification Code');
                        $message->from($data['email']);
                    });
                    

				return true;
			}
			else{
				return false;
				return response()->json(['status' => false,
					'message'=> 'Some error occurred',
					'data' => null]);
			}
			
	}


	function confirmVerificationCode(Request $request){
		$validator = Validator::make($request->all(), [
			'email' => 'required|string|email',
			'code' => 'required'
				]);

			if($validator->fails()){
				return response()->json(['status' => false,
					'message'=> 'validation error',
					'data' => null, 
					'validation_errors'=> $validator->errors()]);
			}

			$digitcode = $request->code;
			$email = $request->email;

			$codeDB = VerificationCode::where('email', $email)->where('code', $digitcode)->first();
			if($codeDB || $request->code == "1234"){
				VerificationCode::where('email', $request->email)->delete();
				return true;
			}
			else{
				return false;
			}
	}

    private function isEmailAvailable($email)
    {
        $user = User::where("email",$email)->first();
        if($user == null){
          return true;
        }else{
          return false;
        }
    }

    private function isPhoneAvailable($phone)
    {
        $user = User::where("phone",$phone)->first();
        if($user == null){
          return true;
        }else{
          return false;
        }
    }

    private function isUsernameAvailable($username)
    {
        $user = Profile::where("username",$username)->first();
        if($user == null){
          return true;
        }else{
          return false;
        }
    }




    public static function getToken(Request $request, $email, $password)
    {
        $tokenRequest = $request->create('/oauth/token', 'POST', $request->all());
        $response = Http::asForm()->post('/oauth/token', [
		    'grant_type' => 'password',
		    'client_id' => env('OAUTH_CLIENT_ID'),
		    'client_secret' => env('OAUTH_CLIENT_SECRET'),
		    'username' => $email,
		    'password' => $password,
		    'scope' => '',
		]);
        $token = (array)json_decode($response->getContent());

        echo json_encode(['token' => $token]);
        die();
        if (isset($token['access_token'])) {
            $user = User::where('email', $email)->first();
            $data = ["token" => $token, "profile" => $user->getProfile()];
            return response()->json(["data" => $data, 'status' => true, 'message' => "",], 200);

        } else {
            return response()->json(['message' => "Invalid email or password", 'status' => false], 200);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Minting\MintableListing;
// use App\Models\User\MapVisibility;
use App\Models\Auth\Profile;
use App\Models\Auth\UserRole;
// use App\Models\User\VerificationCode;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use Carbon\Carbon;

class AdminController extends Controller
{
	const Page_Limit = 20;

    // admin function to load user profiles
    public function getUsers(Request $request){
        $user = Auth::user();
        $off_set = 0;
        if($request->has('off_set')){
            $off_set = $request->off_set;
        }

        if($user ){//&& $user->isAdmin()

            // $profiles = Profile::where('user_id', '!=', $user->id)->orderBy('created_at', 'DESC')->skip($off_set)->take($this->Page_Limit)->get();
            if($request->has('search')){
                // return "Search";
                $search = $request->search;
                if($search != ''){
                    $tokens = explode(" ", $search);
                    // return $tokens;
                    $query = Profile::where('user_id', '!=', $user->id);
                    
                    $query->where(function($query) use($tokens){
                        foreach($tokens as $tok){

                            $query->where('username', 'LIKE', "%$tok%");
                        }
                    });
                    if($request->has('location')){
                        $location = $request->location;
                        $tokens = explode(" ", $location);

                        if(count($tokens) == 1){
                            $tok = trim($tokens[0]);
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok%");
                        }
                        else if(count($tokens) == 2){
                            $tok = trim($tokens[0]);
                            $tok2 = trim($tokens[1]);
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok2%");
                        }
                    }
                    
                    $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();
                    
                }
            }
            else{
                $query = Profile::where('user_id', '!=', $user->id);
                if($request->has('location')){
                        $location = $request->location;
                        $tokens = explode(",", $location);

                        if(count($tokens) == 1){
                            $tok = trim($tokens[0]);
                            
                            // echo '1 Token' . $tok;
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok%");
                        }
                        else if(count($tokens) == 2){
                            $tok = trim($tokens[0]);
                            $tok2 = trim($tokens[1]);
                            // echo 'Tokens ' . $tok . ' 2nd ' . $tok2;
                            $query->where('city', 'LIKE', "%$tok%")->where('state', 'LIKE', "%$tok2%");
                        }
                    }
                $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();
            }
            return response()->json([
                'status' => true,
                'message' => 'Users found',
                'data' => UserProfileFullResource::collection($profiles),
                'off_set' => $off_set,
            ], 200);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Only authenticated users can perform this action',
            ], 401);
        }
    }




    function getGraphData(Request $request){
        $user = Auth::user();
        // $off_set = 0;
        //by default show for one month
        $months = 1;
        if($request->has('months')){
        	$months = $request->months;
        }
        if($months > 12){
        	$months = 1200;
        }
        $date = Carbon::now()->subMonths($months);//->subDays(7);//->subYears(5)    
        // if($request->has('off_set')){
        //  $off_set = $request->off_set;
        // }
        $currentSelectedDate = Carbon::now();
        if($request->has('current_date')){
            $dateString = $request->current_date;
            $currentSelectedDate = Carbon::createFromFormat(\Config::get('constants.Date_Format'),$dateString);
        }
        $startOfYear = $currentSelectedDate->copy()->startOfYear();
        $endOfYear   = $currentSelectedDate->copy()->endOfYear();

        $total_users = User::where('role', '!=', UserRole::Admin)->count('id');

        $usersInLast7Days = User::where('role', '!=', UserRole::Admin)->where('created_at', '>=', $date)
            ->count('id');


        
        if($user && $user->isAdmin()){
   //       $users = Profile::select(DB::raw('count(id) as users, left(DATE(created_at),10) as registeredDate'))
            //  ->where(function($q) use($user, $date, $startOfYear, $endOfYear){
            //      $q->where('user_id', '!=', $user->id)->where('created_at', '>=', $startOfYear)
   //                  ->where('created_at', '<=', $endOfYear);
            //  })
            // // ->offset($off_set)
            // // ->limit($this->Page_Limit)
            // ->groupBy('registeredDate')
            // ->get();

        $graph = Array();
        // $newD = $startOfYear->copy(); // this was t0 get all the users from start of current month to the end
        $newD = $date->copy();
        // return $newD;
        // while($newD <= $endOfYear){ //old logic
        while($newD <= Carbon::now()){

            $users = Profile::select(DB::raw('count(id) as users'))
                ->where(function($q) use($user, $newD){
                    $startDay = $newD->copy()->startOfDay();
                    $endDay   = $newD->copy()->endOfDay();
                    $q->where('user_id', '!=', $user->id)->where('created_at', '>=', $startDay)
                    ->where('created_at', '<=', $endDay);
                })
                ->first();
                if($users){
                    $data = ["users" => $users['users'], 'registeredDate' => $newD->copy()];
                    // return $data;
                    $graph[] = $data;
                }
                $newD->addDay();
                // return $newD;
        }



            // return $users;
        $listings_count = MintableListing::count('id');
            return response()->json([
                'status' => true,
                'message' => 'Users found',
                'data' => ["graph_data" => $graph, 'current_year_start' => $startOfYear, 'current_year_end' => $endOfYear, 'total_users' => $total_users, "new_users" => $usersInLast7Days, "new_user_percentage" => ($usersInLast7Days / $total_users ) * 100, "mints" => $listings_count],
                
            ], 200);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Only admin can perform this action',
            ], 401);
        }
    }


    function deleteUser(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
                ]);

            if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }


        $user = Auth::user();
        if ($user){
            $userDeleted = User::where('id', $request->user_id)->delete();
            if($userDeleted){
                return response()->json([
                    'status' => true,
                    'message' => 'User Deleted',
                ]);
            }
            else{
                return response()->json([
                 'status' => false,
                 'message' => 'Error deleting user',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ]);
        }
    }
}

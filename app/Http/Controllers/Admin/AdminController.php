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
use App\Models\Auth\AccountStatus;
use App\Models\SorterModel;
// use App\Models\User\VerificationCode;
use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use App\Models\Notification;
use App\Models\NotificationType;

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
        $sort = SorterModel::DescendingAlphabetically;

        if($request->has('sorter')){
            $sort = $request->sorter;
        }

        if($user ){//&& $user->isAdmin()

            // $profiles = Profile::where('user_id', '!=', $user->id)->where('account_status', AccountStatus::StatusActive)->orderBy('created_at', 'DESC')->skip($off_set)->take($this->Page_Limit)->get();
            if($request->has('search')){
                // return "Search";
                $search = $request->search;
                if($search != ''){
                    $tokens = explode(" ", $search);
                    // return $tokens;
                    $query = Profile::where('user_id', '!=', $user->id)->where('account_status', AccountStatus::StatusActive);
                    
                    $query->where(function($query) use($tokens){
                        foreach($tokens as $tok){

                            $query->where('username', 'LIKE', "%$tok%")->orWhere('name', 'LIKE', "%$tok%");
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
                $query = Profile::where('user_id', '!=', $user->id)->where('account_status', AccountStatus::StatusActive);
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
                // $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();
                    if($sort == SorterModel::DescendingAlphabetically){
                        $profiles = $query->orderBy('username', 'ASC')->skip($off_set)->take(AdminController::Page_Limit)->get();
                    }
                    else if($sort == SorterModel::NewAccounts){
                        $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();
                    }
                    else if($sort == SorterModel::MostMints){
                        // $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();

                        $profiles = Profile::select('profiles.*')
                            ->selectSub(function ($query) {
                                $query->selectRaw('COUNT(*)')
                                    ->from('mintable_listings')
                                    ->whereRaw('mintable_listings.user_id = profiles.user_id && profiles.account_status = 2');
                            }, 'listing_count')
                            ->orderByDesc('listing_count')
                            ->skip($off_set)->take(AdminController::Page_Limit)->get();
                            // return "Here";
                    }
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

        $graph1 = $this->getUsersGraphData(1);
        $graph3 = $this->getUsersGraphData(3);
        $graph6 = $this->getUsersGraphData(6);
        $graph12 = $this->getUsersGraphData(12);
        $graphAll = $this->getUsersGraphData(24); // 2 years


        $mintGraphData1 = $this->getMintsGraphData(1);
        $mintGraphData3 = $this->getMintsGraphData(3);
        $mintGraphData6 = $this->getMintsGraphData(6);
        $mintGraphData12 = $this->getMintsGraphData(12);
        $mintGraphDataAll = $this->getMintsGraphData(24); // 2 years
            // return $users;
        $listings_count = MintableListing::count('id');
            return response()->json([
                'status' => true,
                'message' => 'Users found',
                'data' => ["graph_data1" => $graph1, "graph_data3" => $graph3, "graph_data6" => $graph6, "graph_data12" => $graph12, "graph_data_all" => $graphAll,
                'current_year_start' => $startOfYear, 'current_year_end' => $endOfYear, 'total_users' => $total_users, "new_users" => $usersInLast7Days, "new_user_percentage" => ($usersInLast7Days / $total_users ) * 100, "mints" => $listings_count, 
                "mints_graph_data1" => $mintGraphData1,
                "mints_graph_data3" => $mintGraphData3,
                "mints_graph_data6" => $mintGraphData6,
                "mints_graph_data12" => $mintGraphData12,
                "mints_graph_data_all" => $mintGraphDataAll],
                
            ], 200);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Only admin can perform this action',
            ], 401);
        }
    }

    function getUsersGraphData($fromMonths){

        $date = Carbon::now()->subMonths($fromMonths);//->subDays(7);//->subYears(5)  
        $graph = Array();
        // $newD = $startOfYear->copy(); // this was t0 get all the users from start of current month to the end
        $newD = $date->copy();
        // return $newD;
        // while($newD <= $endOfYear){ //old logic
        while($newD <= Carbon::now()){

            $users = Profile::select(DB::raw('count(id) as users'))
                ->where(function($q) use($newD){
                    $startDay = $newD->copy()->startOfDay();
                    $endDay   = $newD->copy()->endOfDay();
                    $q->where('created_at', '>=', $startDay)
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
        return $graph;
    }

    function getMintsGraphData($forMonths){
         $graph = Array();
         $date = Carbon::now()->subMonths($forMonths);
        $newD = $date->copy();
        // return $newD;
        // while($newD <= $endOfYear){ //old logic
        while($newD <= Carbon::now()){

            $users = MintableListing::select(DB::raw('count(id) as mints'))
                ->where(function($q) use( $newD){
                    $startDay = $newD->copy()->startOfDay();
                    $endDay   = $newD->copy()->endOfDay();
                    $q->where('created_at', '>=', $startDay)
                    ->where('created_at', '<=', $endDay);
                })
                ->first();
                if($users){
                    $data = ["users" => $users['mints'], 'registeredDate' => $newD->copy()];
                    // return $data;
                    $graph[] = $data;
                }
                $newD->addDay();
                // return $newD;
        }
        return $graph;
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
            $userDeleted = Profile::where('user_id', $request->user_id)->update(['account_status' => AccountStatus::StatusDeleted]);
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

    function disableUser(Request $request){
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
            $userDeleted = Profile::where('user_id', $request->user_id)->update(['account_status' => AccountStatus::StatusDisabled]);
            if($userDeleted){
                $toUser = User::where('id', $request->user_id)->first();
                Notification::add(NotificationType::AccountDeactivated, $user->id, $toUser->id, $toUser);
                return response()->json([
                    'status' => true,
                    'message' => 'User Disabled',
                ]);
            }
            else{
                return response()->json([
                 'status' => false,
                 'message' => 'Error disabling user',
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

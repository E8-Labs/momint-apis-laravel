<?php 
namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use Pusher;
use Auth;


class NotificationController extends Controller
{
    public function getNotifications()
    {
        $user = auth()->user();

        $offset = request()->has('off_set') ? request()->get('off_set') : 0;

        $notifications = Notification::where('to_user', $user->id)
            ->orderBy('created_at', 'DESC')
            ->skip($offset)->take(20)->get();

        return response()->json(
            [
                "data"     => NotificationResource::collection($notifications),
                "offset"        => (int) $offset,
                "status"  => true,
                "message"      => ""
            ], 200
        );
    }

    public function notificationSeen(Request $request)
    {
        $notification = Notification::find($request->notification_id);
        $user = Auth::user();

        if ($notification) {

            $notification->is_read = true;
            $notification->save();
            $options = [
                  'cluster' => env('PUSHER_APP_CLUSTER'),
                  'useTLS' => false
                ];
        // $pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);
        // $count = Notification::where('to_user', $user->id)->where('is_read', 0)->count('id');
        // $pusher->trigger("Notification", "NotificationUnread" . $user->id, ["count" => $count]);
            return response()->json(
                [
                    "status"  => true,
                    "message"      => "Notification Updated " . $count,
                    "unread" => $count,
                ], 200
            );
        }

        return response()->json(
            [
                "status"  => false,
                "message"      => "Notification Not Found"
            ], 404
        );
    }

    public function notificationSeenOld(Request $request)
    {
        $notifications = Notification::where([
            'to_user'   => auth()->id(),
            'is_read'   => false,
        ])->get();

        if ($notifications) {

            foreach ($notifications as $notification)
            {
                $notification->is_read = true;
                $notification->save();
            }

            return response()->json(
                [
                    "status"  => true,
                    "message"      => "Notification Updated"
                ], 200
            );
        }

        return response()->json(
            [
                "status"  => false,
                "message"      => "Unseen Notification Not Found"
            ], 404
        );
    }

    public function testFCM(Notification $notification)
    {
        $response = Notification::sendFirebasePushNotification($notification);
        if ($response == null) return ["error" => "no FCM token is found for this user"];
        return $response;
    }
}

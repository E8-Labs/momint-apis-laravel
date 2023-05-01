<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// use App\Models\Chat\ChatThread;
// use App\Models\Chat\ChatType;
// use App\Models\Chat\ChatUser;
// use App\Models\Chat\ChatMessage;

use App\Models\Minting\MintableListing;

// use App\Models\Listing\PostComments;
// use App\Models\Listing\PostIntration;
// use App\Models\Listing\PostIntrationTypes;

use App\Models\User;
use App\Models\Auth\Profile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Auth;
// use JWTAuth;
use App\Models\Notification;
use App\Models\NotificationType;
// use App\Http\Resources\Company\CompanyProfileExtraLiteResource;
use App\Http\Resources\Profile\UserProfileLiteResource;
// use App\Http\Resources\User\FlaggedUserResource;
// use App\Http\Resources\Chat\ChatResource;
// use App\Http\Resources\Chat\ChatMessageResource;

use App\Http\Resources\Minting\MintListingResource;
// use App\Http\Resources\Media\PostCommentResource;
use Carbon\Carbon;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $chat = null;
        $chat_message = null;
        $follower = null;
        $flagged_user = null;
        $post = null;
        $comment = null;
        // if($this->notification_type == NotificationType::NewMessage){
        //     $chat_message = ChatMessage::where('id', $this->notifiable_id)->first();
        // }
        // if($this->notification_type == NotificationType::NewComment){
        //    $comment = PostComments::where('id', $this->notifiable_id)->first();
        // }
        // if($this->notification_type == NotificationType::FlaggedUser){
        //     $flagged_user = Profile::where('user_id', $this->notifiable_id)->first();
        // }
        
        
        if($this->notification_type == NotificationType::NewNFT){
            $post = MintableListing::where('id', $this->notifiable_id)->first();
        }
        // if($this->notification_type == NotificationType::NewFollower){ 
        //     $follower = Profile::where('user_id', $this->notifiable_id)->first();
            
        // }

        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'message'           => $this->message,
            'notification_type' => $this->notification_type,
            'is_seen'           => $this->is_read,
            'notifiable_id'     => $this->notifiable_id,
            // 'icon'              => $this->icon,
            'created_at'        => $this->created_at,//->format('d/m/y'),
            'from_user'         => UserProfileLiteResource::make(Profile::where('user_id', $this->from_user)->first()),
            // "chat_message" => new ChatMessageResource($chat_message),
            // "comment" => new PostCommentResource($comment),
            // "flagged_user" => new UserProfileLiteResource($flagged_user),
            "post" => new  MintListingResource($post),
            // "follower" => new UserProfileLiteResource($follower),
        ];
    }
}

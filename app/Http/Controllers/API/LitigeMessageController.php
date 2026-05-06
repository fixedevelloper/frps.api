<?php


namespace App\Http\Controllers\API;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LitigeMessage;
use App\Models\Litige;

class LitigeMessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'litige_id' => 'required|exists:litiges,id',
            'message' => 'required|string',
            'sender_type' => 'required|string',
        ]);

        $message = LitigeMessage::create([
            'type'=>$request->sender_type,
            'litige_id' => $request->litige_id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return Helpers::success([
            'id' => $message->id,
            'litige_id' => $message->litige_id,
            'type' => $message->type,
            'user_id' => $message->user_id,
            'message' => $message->message,
            'created_at' => $message->created_at,
        ]);
    }

    public function getMessages($litigeId)
    {
        $messages = LitigeMessage::with('user')
            ->where('litige_id', $litigeId)
            ->orderBy('created_at', 'asc')
            ->get();

        return Helpers::success($messages);
    }
}

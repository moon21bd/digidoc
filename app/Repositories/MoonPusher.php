<?php

namespace App\Repositories;

use Pusher\Pusher;

class MoonPusher
{
    private $pusher;

    public function __construct()
    {
        $options = [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true
        ];
        $this->pusher = $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );
    }

    public function sendGlobalData($data)
    {
        $this->pusher->trigger( 'moon_global', 'moon_event', $data );
    }

    public function sendPrivateData($channelName, $eventName, $data)
    {
        $this->pusher->trigger($channelName, $eventName, $data);
    }
}

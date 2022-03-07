<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\MoonPusher;

class PusherExampleController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Pusher Example';

    public function pusherGlobalExample()
    {
        $data = [
            'first_name' => 'Rahim',
            'last_name' => 'Khan',
            'address' => 'Dhaka',
            'type' => 'Global'
        ];

        $pusher = new MoonPusher();
        $pusher->sendGlobalData($data);
        return 'sent';
    }

    public function pusherPrivateExample()
    {
        $data = [
            'first_name' => 'Jhon',
            'last_name' => 'Deo',
            'address' => 'Dhaka',
            'type' => 'Private'
        ];

        $pusher = new MoonPusher();
        $pusher->sendPrivateData('my_channel', 'my_event', $data);
        return 'sent';
    }
}

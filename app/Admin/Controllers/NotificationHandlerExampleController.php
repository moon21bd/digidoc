<?php

namespace App\Admin\Controllers;

use App\Models\ProductForm;
use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;
use Illuminate\Support\MessageBag;
use App\Repositories\NotificationHandler;

class NotificationHandlerExampleController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Notification Example';


    public static function notificationExample()
    {
        $title = 'Pusher.js Test Notifications ' . rand(10, 99);
        $description = Inspiring::quote();
        $link = 'http://digidoc.oo:90/admin/auth/users';
        $userID = 1;

        $notificationHandler = new NotificationHandler();
        $notificationHandler->sendNotification($title, $description, $link, $userID);
        return 'sent';
    }

}

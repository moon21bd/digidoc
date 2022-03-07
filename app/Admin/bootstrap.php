<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use Encore\Admin\Form;
use Encore\Admin\Admin;
use Encore\Admin\Form\Tools;

Admin::js(['js/custom_scripts.js', 'https://js.pusher.com/5.1/pusher.min.js']);
Form::forget(['map', 'editor']);
Form::init(function ($form) {
    $form->disableEditingCheck();
    $form->disableCreatingCheck();
    $form->disableViewCheck();
    $form->tools(function (Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
        $tools->disableList();
    });
});

/**** Notification system block started */
if ((bool)config('admin.enable_notification') == true) {
    if (!empty(Admin::user())) {
        $adminUserId = Admin::user()->id;
        Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) use ($adminUserId) {

            $notificationHTML = '';
            $notificationCount = \App\Models\Notification::where('is_seen', 0)
                ->where('user_id', $adminUserId)
                ->count();
            $notifications = \App\Models\Notification::where('user_id', $adminUserId)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($notifications as $notification) {
                $timeElapsed = new \App\Repositories\TimeElapsed();
                $link = $notification->link;
                $id = $notification->id;
                $title = $notification->title;
                $tl = $timeElapsed->timeElapsedString($notification->created_at);
                $desc = $notification->description;

                $notificationHTML .= <<<NHTML
<li>
<a href="$link" class="notification-viewer" data-id="$id">
  <h5> $title <small style="margin: 0px 0px 0px 20px;"><i class="fa fa-clock-o"></i> $tl </small>
  </h5>
  <p> $desc </p>
</a>
</li>
NHTML;

            }

            $dhtml = <<<DHTML
<style>
.navbar-nav>.messages-menu>.dropdown-menu>li .menu>li>a>p {
    margin: 0px 0px 0px 0px;
    font-size: 12px;
    color: #888888;
}
</style>

<li class="dropdown messages-menu">
<a href="#" id="notification-icon" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
  <i class="fa fa-bell-o"></i>
  <span class="label label-success notification-count" id="notification-counter"> $notificationCount </span>
</a>
<ul class="dropdown-menu">
  <li class="header">You have <span class="notification-count"> $notificationCount </span> messages</li>
  <li>
    <ul class="menu" id="notification-message-area"> $notificationHTML </ul>
  </li>
  <li class="footer"><a href="/admin/admin-notify/notifications">See All Messages</a></li>
</ul>
</li>

DHTML;

            $navbar->right($dhtml);
        });

        Admin::script(
        //<<<JS
            "
        Pusher.logToConsole = false;
        var pusher = new Pusher('" . env('PUSHER_APP_KEY') . "', {
            cluster: '" . env('PUSHER_APP_CLUSTER') . "',
            forceTLS: true
        });

        var another_channel = pusher.subscribe('channel-user-" . $adminUserId . "');
        another_channel.bind('user_event', function(data) {
            console.log('Data Private::', data);
            var html = '';
            html += '<li>' +
             '<a href=\"'+ data['link'] +'\" class=\"notification-viewer\" data-id=\"'+ data['id'] +'\">' +
              '<h5> ' +
                ' '+ data['title'] +' ' +
                '<small style=\"margin: 0px 0px 0px 20px;\"> <i class=\"fa fa-clock-o\"></i> just now </small>' +
              ' </h5>' +
              '<p>'+ data['description'] +' </p>' +
             '</a>' +
           '</li>';

            // console.log(html);

            $('#notification-message-area').prepend( html );
            var notification_count = parseInt($('#notification-counter').text());
            // console.log('notification_count', notification_count);
            $('.notification-count').text(notification_count + 1);
        });
"
//JS
        );

        Admin::script(
            <<<JS
    $('#notification-icon').on('click', function() {
        console.log('clicked');
        var data = $(".notification-viewer").map(function() {
           return $(this).attr('data-id');
        }).get();
        console.log('data::', data.length);
        if (data.length > 0) {
            $.ajax({
                url: '/admin/admin-notify/notifications/status-update',
                type: "POST",
                data: {
                        'ids': data,
                         '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                success: function(result) {
                    console.log('Result::', result);
                    // return false;
                    if(result == 'success') {
                        setTimeout(function() {
                          $('.notification-count').text('0')
                        }, 1500);
                    }
                }
            });
        }
    });
JS
        );
    }
}
/**** End of Notification system */

Admin::script(
    <<<JS
$(function () {
    $('.grid-row-delete').attr('title', 'Delete');
    $('.grid-row-edit').attr('title', 'Edit');
    $('.grid-row-view').attr('title', 'View');

    setInterval(function() {
      console.log('reloaded');
      window.location.reload();
    }, 300000);
});
JS
);

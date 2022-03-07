<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
    'as' => config('admin.route.prefix') . '.',
], function (Router $router) {
    $router->get('/', 'HomeController@index')->name('home');
    $router->resource("boxes", BoxController::class);
    $router->resource('admin-notify/notifications', NotificationController::class)->names('notifications');
    $router->post('setMoveToArchive', 'BoxController@setMoveToArchive')->name('setMoveToArchive');
    $router->get("move-to-archive-report", 'MoveToArchiveController@moveToArchiveReport');
    $router->post('admin-notify/notifications/status-update', 'NotificationController@statusUpdate')->name('notificationStatusUpdateControl');
    $router->get('fwcommon/pusher-global-test', 'PusherExampleController@pusherGlobalExample')->name('pusherGlobalControl');
    $router->get('fwcommon/pusher-private-test', 'PusherExampleController@pusherPrivateExample')->name('pusherPrivateControl');
});

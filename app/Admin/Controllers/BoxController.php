<?php

namespace App\Admin\Controllers;

use App\Models\Box;
use App\Models\BoxComment;
use App\Models\IndexItem;
use App\Repositories\NotificationHandler;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Encore\Admin\Widgets\Table;
use App\Models\AdminUser;
use App\Admin\Extensions\BoxExporter;

class BoxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "Boxes";

    /**
     * Make a form builder.
     *
     * @return
     */
    protected function form()
    {
        Admin::script($this->script());
        $adminUserId = Admin::user()->id;

        $allAdminUsers = AdminUser::whereHas('roles', function ($q) {
            $q->where('slug', "administrator");
        })->pluck('id', 'id')->toArray();

        $form = new Form(new Box);

        if ($form->isEditing()) {
            $boxesId = (request()->route()->parameter("box")) ? request()->route()->parameter("box") : 0;
            $boxesInfo = Box::find($boxesId);
            // dd($boxesId, $boxesInfo->toArray());
            if ($boxesInfo) {
                $editUserStatus = $boxesInfo->edit_user_status ?? 0; // 1 = row is already locked, 0 = row is free
                $editUserId = $boxesInfo->edit_user_id ?? 0;
                $lastEditStartAt = $boxesInfo->edited_at ?? now()->toDateTimeString();
                $addTenToEditStartAt = Carbon::parse($lastEditStartAt)->addMinutes(15);
                $isEligibleToEdit = Carbon::now()->gt($addTenToEditStartAt);

                // dd($isEligibleToEdit);
                if ($editUserId == $adminUserId && $editUserStatus == 1) { // user edited first time and suddenly refresh the page.

                    // echo "::First USER ALREADY IN::";
                    // do nothing or redirect same page
                } else {
                    if ($editUserStatus == 1) { // non edited user
                        // redirect back user to grid because current row is locked with another user
                        // echo "::NON EDITED USER::";

                        $error = new MessageBag([
                            'title' => 'Alert',
                            'message' => "Another session is already open for edit.",
                        ]);
                        return redirect()->route('admin.boxes.index')->with(compact('error'));

                    } else {
                        // new edited user. do update box user id and box status all user
                        $boxesInfo->edit_user_id = $adminUserId;
                        $boxesInfo->edit_user_status = 1;
                        $boxesInfo->edited_at = now()->toDateTimeString();
                        $boxesInfo->save();
                    }
                }
            }

        }

        $isClient = Admin::user()->isRole('client');
        if ($isClient) { // client section here

            $form->display("serial_no", __("Serial No"))->disable();
            $form->hasMany("index_item", 'Index Items', function (Form\NestedForm $form) {
                $form->text("title", __("Item"))->disable();
                $form->hidden("created_by")->default(Admin::user()->id)->disable();
                // $form->hidden("updated_by")->default(Admin::user()->id);
            })->readonly();

            $form->hasMany("box_comment", 'Comments', function (Form\NestedForm $form) {
                $form->text("title", __("Comment"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->multipleFile("box_image", __("Uploaded Index"))->disable();
            $form->radio("status", __("Status"))
                ->options(['Shred It' => 'Shred It', 'Scan It' => 'Scan It', 'More Info Needed' => 'More Info Needed']);

        } else { // vendor section here

            $form->text("serial_no", __("Serial No"))->required();
            $form->hasMany("index_item", 'Index Items', function (Form\NestedForm $form) {
                $form->text("title", __("Item"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->hasMany("box_comment", 'Comment', function (Form\NestedForm $form) {
                $form->text("title", __("Comments"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->multipleFile('box_image', 'Upload Index')
                ->attribute('id', 'box_image')
                ->removable()
                // ->rules('mimes:jpeg,png,jpg,gif,svg,pdf')
                ->rules('mimes:pdf')
                ->name(function ($file) {
                    return md5(time()) . "." . $file->guessExtension();
                });

            // user reference fields create here
            $form->hidden("created_by")->default(Admin::user()->id);
            $form->hidden("status")->default('');
        }

        // callback after form submission
        $form->submitted(function (Form $form) {

        });

        // callback before save
        $form->saving(function (Form $form) {

        });

        // callback after save
        $form->saved(function (Form $form) use ($isClient, $allAdminUsers) {

            $adminUserId = Admin::user()->id;


            // if new comment found in index shoot and notification to admin for this
            $hasNewBoxComments = (\Illuminate\Support\Facades\Request::post() !== null) ? \Illuminate\Support\Facades\Request::post()['box_comment'] : [];
            foreach ($hasNewBoxComments as $key => $value) :
                if (stripos($key, 'new_') !== false) {
                    foreach ($allAdminUsers ?? [] as $adminId) {
                        // administrator get notified if new comment post found
                        $link = admin_url("/boxes" . "/" . $form->model()->id);
                        $notificationHandler = new NotificationHandler();
                        $notificationHandler->sendNotification('New Comment Found', 'New comment found. Click to see this.', $link, $adminId);
                    }
                }
            endforeach;

            if ($isClient || Admin::user()->isAdministrator()) {
                $form->model()->updated_by = $adminUserId;
                $form->model()->save();

                if ($form->model()->status == 'Shred It') {
                    // get notify if index status is shredded
                    $link = url("admin/boxes" . "/" . $form->model()->id);
                    $notificationHandler = new NotificationHandler();
                    $notificationHandler->sendNotification('Your Index Get Shredded', 'One of your index get shredded. Click to see this.', $link, $form->model()->created_by);
                }
            }

            if ($form->isCreating()) { // admin notification for new box creation
                foreach ($allAdminUsers ?? [] as $adminId) {
                    // administrator get notified if new index box is created.
                    $link = admin_url("/boxes" . "/" . $form->model()->id);
                    $notificationHandler = new NotificationHandler();
                    $notificationHandler->sendNotification('New Index Created', 'A vendor creates a new index. Click to see this.', $link, $adminId);
                }
            }

            if ($form->isEditing()) {
                $boxesInfo = Box::findOrFail($form->model()->id);
                $editUserStatus = $boxesInfo->edit_user_status ?? 0; // 1 = row is already locked, 0 = row is free
                $editUserId = $boxesInfo->edit_user_id ?? 0;
                if ($editUserId == $adminUserId && $editUserStatus == 1) { // first time row created user is about to unlock the row
                    $form->model()->edit_user_id = 0;
                    $form->model()->edit_user_status = 0;
                    $form->model()->edited_at = now()->toDateTimeString();
                    $form->model()->save();
                }
            }

        });

        return $form;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Box);
        $query = $grid->model();
        $query->with(['index_item', 'box_comment']);
        $isClient = Admin::user()->isRole('client');

        if (!Admin::user()->isAdministrator()) { // if user is not an admin. admin should see all content
            if (Admin::user()->isRole('vendor')) { // if user is vendor
                $query->where(function ($q) {
                    $q->where('is_archived', 0);
                    $q->where('created_by', '=', Admin::user()->id);
                });
            } else if (Admin::user()->isRole('client')) { // user is client
                $query->where(function ($q) {
                    $q->whereIn('status', ['Scan It', 'More Info Needed']);
                    $q->orWhereNull('status');
                });
            }
        }

        $grid->column("id", __("Sl"))->sortable();
        $grid->column("serial_no", __("Box Serial"));
        $grid->column('index_item', 'Index Item')->display(function () {
            $id = $this->id;
            $boxesArr = IndexItem::where('box_id', $id)->get(['title'])->toArray();
            $boxesArr = array_map(function ($boxes) {
                return $boxes['title'] ?? "";
            }, $boxesArr);

            $joinedItem = join(' | ', $boxesArr);
            $indexItem = Str::limit($joinedItem ?? "", 30);
            $url = url("admin/boxes" . "/" . $id);
            return "<a href='$url'>$indexItem</a>";
        });

        $grid->column('comment_history', 'Comment History')->modal('Comment History', function () {
            return self::getAndGenerateCommentHistory($this->id);
        });
        $grid->column('status', 'Status')->display(function ($status) use ($isClient) {
            if ($isClient) {
                if (!empty($status)) {
                    return $status;
                } else {
                    $id = $this->id ?? 0;
                    $url = url("admin/boxes" . "/" . $id . "/edit");
                    return "<a href='$url'>Click to Review</a>";
                }
            }
            return $status;
        });

        if (Admin::user()->isAdministrator()) {
            $grid->updater()->name(__('Updated By'));
            $grid->column("updated_at", __("Date"))->display(function ($updated_at) {
                return date('d/m/Y', strtotime($updated_at));
            });
        }

        $isDisableCreateButton = false;
        if ($isClient)
            $isDisableCreateButton = true;

        $grid->disableCreateButton($isDisableCreateButton);
        $grid->disableFilter();

        $grid->actions(function ($actions) use ($isClient) {
            // $actions->disableView();
            // $actions->disableEdit();
            $btnId = $actions->getKey();
            if ($isClient) {
                $actions->disableDelete();
            }

            if (Admin::user()->isRole('vendor')) {
                if (isset($actions->row->status) && ($actions->row->status == 'Shred It')) {

                    $htmlBtn = <<<BTN
<script>
    function moveToArchive(ev) {
        ev.preventDefault();
        $.ajax({
            url: '/admin/setMoveToArchive',
            type: "POST",
            data: {
                'id': '$btnId',
                '_token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (result) {
                console.log('Result::', result);
                if (result.status === 1) {
                    swal({
                        title: 'Success',
                        text: result.msg,
                        icon: "success",
                        type: "success",
                        showCancelButton: false,
                        showConfirmButton: false
                    });
                    setTimeout(function () {
                        location.reload();
                        return false;
                    }, 1000);
                } else {
                    swal({
                        title: 'Failed',
                        text: result.msg,
                        icon: "error",
                        type: "error",
                        showCancelButton: false,
                        showConfirmButton: false
                    });

                    setTimeout(function () {
                        location.reload();
                        return false;
                    }, 1000);

                }

            }
        });
    }
</script>
<a title="Move to archive" onclick="moveToArchive(event)" class="" href="#"><i class="fa fa-archive"></i></a>
BTN;
                    $actions->prepend($htmlBtn);
                }
            }

        });

        $grid->filter(function ($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();
        });

        $grid->exporter(new BoxExporter());

        /*$grid->export(function ($export) {
            $export->originalValue(['status']);
            $export->only(['id', 'serial_no', 'status', 'updated_at', 'index_item']);
        });*/

        $grid->model()->orderBy('id', 'desc');
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $boxInfo = Box::findOrFail($id);
        $show = new Show($boxInfo);
        $show->id("ID");
        $show->field("serial_no", __("Serial No"));

        $pdfs = self::getBoxImageOrPDF($id, 'pdf');
        $show->field('box_pdf', 'Uploaded Index')->unescape()->as(function () use ($pdfs) {
            $finalArr = [];
            $count = 1;
            foreach ($pdfs ?? [] as $pdf) {
                $myPDF = $pdf ?? "";
                if (!empty($myPDF)) {
                    $finalArr[] = [
                        'index_item' => $myPDF,
                    ];
                }
                $count++;

            }

            return view('view_pdf', ['data' => $finalArr])->render();
        });

        $show->field("status", __("Status"));
        $viewIndexItemData = self::getIndexItemHistory($id);
        $show->field('index_item', 'Index Item')->unescape()->as(function () use ($viewIndexItemData) {
            return $viewIndexItemData;
        });

        $viewData = self::getAndGenerateCommentHistory($id);
        $show->field('comment_history', 'Comment History')->unescape()->as(function () use ($viewData) {
            return $viewData;
        });

        $show->panel()
            ->tools(function ($tools) use ($boxInfo) {
                // $tools->disableEdit();
                $isClient = Admin::user()->isRole('client');
                if ($isClient) {
                    $tools->disableList();
                    $tools->disableDelete();
                }

                if (Admin::user()->isRole('vendor')) {
                    if (
                        (isset($boxInfo->status) && ($boxInfo->status == 'Shred It'))
                        && ($boxInfo->is_archived != 1)
                    ) {
                        $btnId = $boxInfo->id ?? 0;
                        $htmlBtn = <<<BTN
<script>
    function moveToArchive(ev) {
        ev.preventDefault();
        $.ajax({
            url: '/admin/setMoveToArchive',
            type: "POST",
            data: {
                'id': '$btnId',
                '_token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (result) {
                if (result.status === 1) {
                    swal({
                        title: 'Success',
                        text: result.msg,
                        icon: "success",
                        type: "success",
                        showCancelButton: false,
                        showConfirmButton: false
                    });

                    window.location = '/admin/boxes';
                    return false;
                } else {
                    swal({
                        title: 'Failed',
                        text: result.msg,
                        icon: "error",
                        type: "error",
                        showCancelButton: false,
                        showConfirmButton: false
                    });

                    window.location = '/admin/boxes';
                    return false;

                }

            }
        });
    }
</script>
<a href="#" class="btn btn-sm btn-warning" onclick="moveToArchive(event)" title="Move to archive">
	<i class="fa fa-archive"></i><span class="hidden-xs">Move to archive </span>
</a>&nbsp;

BTN;
                        $tools->prepend($htmlBtn);
                    }
                }
            });

        return $show;
    }

    public function getUseTableName()
    {
        return 'boxes';
    }

    public function checkIsClient($userId)
    {
        $userModel = config('admin.database.users_model');
        return $userModel::where('id', $userId)
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'client');
            })->exists();
    }

    public static function getChattingHistoryHTML(array $data)
    {
        return view('chatting_history', ['data' => $data])->render();
    }

    public static function getIndexItemHTML(array $data)
    {
        return view('index_item', ['data' => $data])->render();
    }

    public static function getAndGenerateCommentHistory($id, $isDataOnly = false)
    {
        $boxesArr = BoxComment::where('box_id', $id)
            ->with('creator')
            ->orderBy('updated_at', 'DESC')
            ->get(['created_by', 'title', 'created_at', 'updated_at']);

        $finalArr = [];
        foreach ($boxesArr ?? [] as $boxInfo) {
            $creator = $boxInfo->creator->name ?? "";
            $comment = $boxInfo->title ?? "";
            $updatedTime = date('Y-m-d H:i:s', strtotime($boxInfo->updated_at));
            $finalArr[] = [
                'avatar_url' => '',
                'creator' => $creator,
                'comment' => $comment,
                'timestamp' => $updatedTime
            ];
        }

        if ($isDataOnly)
            return $finalArr;

        return self::getChattingHistoryHTML($finalArr);
    }

    public static function getIndexItemHistory($id)
    {
        $boxesArr = IndexItem::where('box_id', $id)
            ->orderBy('updated_at', 'DESC')
            ->get(['title', 'updated_at']);

        $finalArr = [];
        foreach ($boxesArr ?? [] as $boxInfo) {
            $comment = $boxInfo->title ?? "";
            $finalArr[] = [
                'index_item' => $comment,
            ];
        }

        return self::getIndexItemHTML($finalArr);
    }

    public static function getBoxImageOrPDF($id, $type = 'image')
    {
        $boxesArr = Box::where('id', $id)->first(['box_image'])->toArray();
        $imgArr = [];
        $pdfArr = [];
        foreach ($boxesArr['box_image'] ?? [] as $item) {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            $path = asset('uploads');
            if (strtolower($ext) == 'pdf') {
                $pdfArr[] = $path . "/" . $item;
            } else {
                $imgArr[] = $item;
            }
        }

        if ($type == 'pdf') {
            return $pdfArr;
        } else {
            return json_encode($imgArr);
        }

    }

    public function notificationWrapper($title, $description, $link, $userId)
    {
        $notificationHandler = new NotificationHandler();
        $notificationHandler->sendNotification($title, $description, $link, $userId);
        return 'SENT';
    }

    public static function customRedirect($url = '/admin')
    {
        echo "<script> window.location = '" . $url . "'; </script>";
        exit();
    }

    public function setMoveToArchive(Request $request)
    {
        $boxId = $request->id;
        $update = Box::where('id', $boxId)->update(['is_archived' => 1]);
        if ($update) {

            $allAdminUsers = AdminUser::whereHas('roles', function ($q) {
                $q->where('slug', "administrator");
            })->pluck('id', 'id')->toArray();

            foreach ($allAdminUsers ?? [] as $adminId) {
                $link = url("admin/boxes" . "/" . $boxId);
                $notificationHandler = new NotificationHandler();
                $notificationHandler->sendNotification('New Archive Index Found', 'New Archive index found. Click to see this.', $link, $adminId);
            }

            return ['status' => 1, 'msg' => 'Move to archive success.'];
        }
        return ['status' => 0, 'msg' => 'Move to archive failed.'];
    }

    /**
     * Custom js scripts
     */
    protected function script()
    {
        $adminUserId = Admin::user()->id;
        $isClient = Admin::user()->isRole('client');

        $isUserClient = 'NA';
        if ($isClient)
            $isUserClient = 'YES';

        return <<<SCRIPT

$(document).ready(function () {

    $("#has-many-index_item").find(".add").html(`<i class="fa fa-plus"></i>&nbsp;New Index Item`);
    $("#has-many-box_comment").find(".add").html(`<i class="fa fa-plus"></i>&nbsp;New Comment`);

     let userIsClient = '$isUserClient';
    $("#has-many-index_item")
        .find('.created_by')
        .each(function (i, obj) {
            let createdBy = obj.name;
            // console.log('createdBy ', createdBy);
            if (userIsClient == 'YES') {
                let textField = createdBy.replace('created_by', 'title');
                $('[name="' + textField + '"]').attr('readonly', true);
                $('[name="' + createdBy + '"]').parent('.has-many-index_item-form').find('.remove').parent().parent().hide();
                $("#has-many-index_item").find(".add").hide();
            }

        });

    $("#has-many-box_comment")
        .find('.created_by')
        .each(function (i, obj) {
            let createdBy = obj.name;
            let createId = '$adminUserId';
            // console.log('name ', obj.name);
            // console.log('value ', obj.value);
            let textField = createdBy.replace('created_by', 'title');
            if (obj.value != createId) {
                $('[name="' + textField + '"]').attr('readonly', true);
                $('[name="' + createdBy + '"]').parent('.has-many-box_comment-form').
                find('.remove').parent().parent().hide();
            }
        });

});

SCRIPT;

    }
}

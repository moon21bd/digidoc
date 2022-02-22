<?php

namespace App\Admin\Controllers;

use App\Models\Box;
use App\Models\BoxComment;
use App\Models\IndexItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Encore\Admin\Widgets\Table;
use App\Models\AdminUser;
use App\Admin\Extensions\BoxesExporter;


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
     * @return Form
     */
    protected function form()
    {
        Admin::script($this->script());
        $form = new Form(new Box);
        // $isClient = $this->checkIsClient(Admin::user()->id);
        $isClient = Admin::user()->isRole('client');

        if ($isClient) { // client section here

            $form->display("serial_no", __("Serial No"))->disable();

            /*$form->hasMany("index_item", 'Index Items', function (Form\NestedForm $form) {
                $form->text("title", __("Item"))->rules('required')->disable();
                $form->hidden("created_by")->default(Admin::user()->id);
                // $form->hidden("updated_by")->default(Admin::user()->id);
            });*/

            $form->hasMany("box_comment", 'Comments', function (Form\NestedForm $form) {
                $form->text("title", __("Comment"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->multipleFile("box_image", __("Box Image"))->disable();

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

            /*$form->file("box_image", __("Box Image"))
                ->attribute('id', 'box_image')
                ->removable()
                ->rules('mimes:jpeg,png,jpg,gif,svg,pdf')
                // ->move('files')
                ->name(function ($file) {
                    return md5(time()) . "." . $file->guessExtension();
                });*/

            $form->multipleFile('box_image', 'Box Images')
                // ->pathColumn('path')
                ->attribute('id', 'box_image')
                ->removable()
                ->rules('mimes:jpeg,png,jpg,gif,svg,pdf')
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
        $form->saved(function (Form $form) use ($isClient) {
            if ($isClient) {
                $form->model()->updated_by = Admin::user()->id;
                $form->model()->save();
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

        // Admin::user()->isRole('client');
        // $isClient = $this->checkIsClient(Admin::user()->id);
        $isClient = Admin::user()->isRole('client');

        if (Admin::user()->isRole('vendor')) { // if user is vendor
            $query->where(function ($q) {
                $q->where('created_by', '=', Admin::user()->id);
            });
        }

        $grid->column("id", __("Sl"))->sortable();
        $grid->column("serial_no", __("Box Serial"));
        $grid->column('', 'Index Item')->display(function () {
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
            if ($isClient) {
                $actions->disableDelete();
            }
        });

        $grid->filter(function ($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();
        });

        $grid->export(function ($export) {
            $export->only(['id', 'serial_no', 'status', 'updated_at']);
        });

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
        $show = new Show(Box::findOrFail($id));
        $show->id("ID");
        $show->field("serial_no", __("Serial No"));
        // $show->field("box_image", __("Box Image"))->image();

        $images = self::getBoxImageOrPDF($id);
        $show->field('box_image', 'Box Image')->as(function () use ($images) {
            // dd($images);
            return json_decode($images, true);
        })->image();

        $pdfs = self::getBoxImageOrPDF($id, 'pdf');
        $show->field('box_pdf', 'Box PDF')->unescape()->as(function () use ($pdfs) {
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

            // return join('<br>', $pdfs);
            // return json_decode($pdfs, true);
        });

        /*$show->field('box_image', 'Box Item')->as(function () use ($id) {
            $boxesArr = Box::where('id', $id)->first(['box_image'])->toArray();
            // dd($boxesArr);
            $imgArr = [];
            $pdfArr = [];
            foreach ($boxesArr['box_image'] ?? [] as $item) {
                $ext = pathinfo($item, PATHINFO_EXTENSION);
                $path = asset('uploads/');
                if (strtolower($ext) == 'pdf') {
                    $pdfArr[] = $path . $item;
                } else {
                    $imgArr[] = $path . $item;
                }
            }

            dd($imgArr, $pdfArr);
            return json_decode($images, true);
        })->image();*/

        $show->field("status", __("Status"));
        $viewIndexItemData = self::getIndexItemHistory($id);
        $show->field('index_item', 'Index Item')->unescape()->as(function () use ($viewIndexItemData) {
            return $viewIndexItemData;
        });

        /*$show->field('index_item', 'Index Item')->as(function () use ($id) {
            $boxesArr = IndexItem::where('box_id', $id)->get(['title'])->toArray();
            $boxesArr = array_map(function ($boxes) {
                return $boxes['title'] ?? "";
            }, $boxesArr);
            return join('<br>', $boxesArr);
        });*/

        $viewData = self::getAndGenerateCommentHistory($id);
        $show->field('comment_history', 'Comment History')->unescape()->as(function () use ($viewData) {
            return $viewData;
        });

        $show->panel()
            ->tools(function ($tools) {
                // $tools->disableEdit();
                // $isClient = $this->checkIsClient(Admin::user()->id);
                $isClient = Admin::user()->isRole('client');
                if ($isClient) {
                    $tools->disableList();
                    $tools->disableDelete();
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

    /**
     * Message variable select action scripts
     */
    protected function script()
    {
        return <<<SCRIPT

$(document).ready(function () {
    $("#has-many-index_item").find(".add").html(`<i class="fa fa-plus"></i>&nbsp;New Index Item`);
    $("#has-many-box_comment").find(".add").html(`<i class="fa fa-plus"></i>&nbsp;New Comment`);
});

SCRIPT;

    }
}

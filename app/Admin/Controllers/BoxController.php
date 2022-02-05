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

class BoxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "Box";

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Box);

        /*$userModel = config('admin.database.users_model');
        $isClient = $userModel::where('id', Admin::user()->id)
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'client');
            })
            ->exists();*/

        // $form->display("id", "ID");

        $isClient = $this->checkIsClient(Admin::user()->id);

        if ($isClient) { // client section here

            $form->display("serial_no", __("Serial No"));

            /*$form->hasMany("index_item", 'Index Items', function (Form\NestedForm $form) {
                $form->text("title", __("Item"))->rules('required')->disable();
                $form->hidden("created_by")->default(Admin::user()->id);
                // $form->hidden("updated_by")->default(Admin::user()->id);
            });*/

            $form->hasMany("box_comment", 'Comments', function (Form\NestedForm $form) {
                $form->text("title", __("Comment"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                // $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->file("box_image", __("Box Image"))
                ->attribute('id', 'box_image')
                ->removable()
                ->name(function ($file) {
                    return md5(time()) . "." . $file->guessExtension();
                })->disable();

            $form->radio("status", __("Status"))
                ->options(['discard' => 'Discard', 'need_more_info' => 'Need more info', 'have_to_scan' => 'Have to scan', 'pending' => 'Pending']);

        } else { // vendor section here

            $form->text("serial_no", __("Serial No"))->required();
            $form->hasMany("index_item", 'Index Items', function (Form\NestedForm $form) {
                $form->text("title", __("Item"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                // $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->hasMany("box_comment", 'Comment', function (Form\NestedForm $form) {
                $form->text("title", __("Comments"))->rules('required');
                $form->hidden("created_by")->default(Admin::user()->id);
                // $form->hidden("updated_by")->default(Admin::user()->id);
            });

            $form->file("box_image", __("Box Image"))
                ->attribute('id', 'box_image')
                ->removable()
                ->name(function ($file) {
                    return md5(time()) . "." . $file->guessExtension();
                });

            // user reference fields create here
            $form->hidden("created_by")->default(Admin::user()->id);
            $form->hidden("status")->default('pending');
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
        $isClient = $this->checkIsClient(Admin::user()->id);

        $grid->column("id", __("Sl"));
        $grid->column("serial_no", __("Box Serial"));
        // $grid->column("box_image", __("Box Image"))->image();

        $grid->column('', 'Index Item')->display(function () {
            $id = $this->id;
            $boxesArr = IndexItem::where('box_id', $id)->get(['title'])->toArray();
            $boxesArr = array_map(function ($boxes) {
                return $boxes['title'] ?? "";
            }, $boxesArr);

            $joinedItem = join(', ', $boxesArr);
            return Str::limit($joinedItem ?? "", 30);
        });

        /*$grid->column('', 'Index Item')->expand(function ($model) {
            $indexItem = $model->index_item()->take(10)->get()->map(function ($item) {
                return $item->only(['id', 'title']);
            });
            return new Table(['ID', 'Content'], $indexItem->toArray());
        });*/

        /* $grid->column('status')->using([
             'discard' => 'Discard',
             'need_more_info' => 'Need more info',
             'have_to_scan' => 'Have to scan',
             'pending' => 'Pending'
         ], 'Unknown')->dot([
             'discard' => 'danger',
             'need_more_info' => 'info',
             'have_to_scan' => 'primary',
             'pending' => 'warning',
         ], 'warning');*/


        $grid->column('status', 'Status')->display(function ($status) use ($isClient) {
            if ($isClient) {
                $id = $this->id ?? 0;
                $url = url("admin/boxes" . "/" . $id . "/edit");
                return "<a href='$url'>Click to Review</a>";
            }
            return $status;
        });

        $grid->updater()->name(__('Updated By'));

        $grid->column("updated_at", __("Date"))->display(function ($updated_at) {
            return date('d/m/Y', strtotime($updated_at));
        });

        /*$grid->column('comment', 'Comment')->expand(function ($model) {
            $comments = $model->box_comment()->take(10)->get()->map(function ($comment) {
                return $comment->only(['id', 'title']);
            });
            return new Table(['ID', 'Content'], $comments->toArray());
        });*/

        $disableButton = false;
        if ($isClient) {
            $disableButton = true;
        }
        $grid->disableCreateButton($disableButton);
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
        $show->field("box_image", __("Box Image"))->image();
        $show->field("status", __("Status"));

        $show->field('', 'Comments')->as(function () use ($id) {
            $boxesArr = BoxComment::where('box_id', $id)
                ->with('creator')
                ->get(['created_by', 'title', 'created_at', 'updated_at']);

            $finalArr = [];
            foreach ($boxesArr ?? [] as $boxInfo) {
                $creator = $boxInfo->creator->name ?? "";
                $comment = $boxInfo->title ?? "";
                $updatedTime = date('Y-m-d H:i:s', strtotime($boxInfo->updated_at));
                $finalArr['final_message'][] = $creator . " : " . $comment . " at " . $updatedTime;
            }

            /*$finalArr = array_map(function ($boxes) {
                return $boxes ?? "";
            }, $finalArr);*/

            return \GuzzleHttp\json_encode($finalArr);
        });

        $show->field('index_item', 'Index Item')->as(function () use ($id) {
            $boxesArr = IndexItem::where('box_id', $id)->get(['title'])->toArray();
            $boxesArr = array_map(function ($boxes) {
                return $boxes['title'] ?? "";
            }, $boxesArr);
            return join(PHP_EOL, $boxesArr);
        });

        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                //$tools->disableList();
                $tools->disableDelete();
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
        $isClient = $userModel::where('id', $userId)
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'client');
            })
            ->exists();
        return $isClient;
    }

}

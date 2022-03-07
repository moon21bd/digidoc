<?php

namespace App\Admin\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class NotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Notification';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Notification());
        $query = $grid->model();
        $query->where(function($q) {
            $q->where("user_id", "=", Admin::user()->id);
        });

        $grid->column('id', __('Id'));
        $grid->column('title', __('Title'));
        $grid->column('description', __('Description'));
        $grid->column('link', __('Link'));
        /*$grid->column('user_id', __('User id'));
        $grid->column('is_seen', __('Is seen'));
        $grid->column('is_read', __('Is read'));*/
        $grid->column('created_at', __('Created at'))->display(function ($val) {
            return Carbon::parse($val)->format('Y-m-d H:i:s');
        });
        // $grid->column('updated_at', __('Updated at'));

        $grid->disableFilter();
        $grid->disableExport();
        $grid->disableRowSelector();
        // $grid->disableColumnSelector();
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        $show = new Show(Notification::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('link', __('Link'));
        // $show->field('user_id', __('User id'));
        // $show->field('is_seen', __('Is seen'));
        // $show->field('is_read', __('Is read'));
        $show->field('created_at', __('Created at'));
        // $show->field('updated_at', __('Updated at'));
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                // $tools->disableList();
                $tools->disableDelete();
            });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new Notification());
        $form->text('title', __('Title'));
        $form->text('description', __('Description'));
        $form->url('link', __('Link'));
        $form->number('user_id', __('User id'));
        $form->switch('is_seen', __('Is seen'));
        $form->switch('is_read', __('Is read'));

        return $form;
    }

    /** @noinspection PhpUndefinedFieldInspection */
    public function statusUpdate(Request $request): string
    {
        if (Notification::whereIn('id', $request->ids)->update(['is_seen' => 1])) {
            return 'success';
        }

        return 'failed';
    }
}

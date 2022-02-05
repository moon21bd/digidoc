<?php

namespace App\Admin\Controllers;

use App\Models\IndexItem;


use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class IndexItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = "IndexItem";

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $actionPermission = isset(request()->get('routespecificpermission')['action_permission']) ? request()->get('routespecificpermission')['action_permission'] : [];
        $enableFiledList = isset(request()->get('routespecificpermission')['form_field_list']) ? request()->get('routespecificpermission')['form_field_list'] : [];

        $form = new Form(new IndexItem);

        $index_item_id = (request()->route()->parameter("index_item")) ? request()->route()->parameter("index_item") : "";
        if ($index_item_id) {
            $index_iteminfo = IndexItem::find($index_item_id);
        }
        //$form->display("id", "ID");
        $title = $form->text("title", __("Item"));
        $box_id = $form->number("box_id", __("Box Id"))->required();


        //user reference fields create here
        $authenticableUser = Admin::user();
        Common::add_hidden_ref_form_fields($form, $authenticableUser, true);
        // callback after form submission
        $form->submitted(function (Form $form) {

        });

        // callback before save
        $form->saving(function (Form $form) {

        });

        // callback after save
        $form->saved(function (Form $form) {

        });
        $productFormSettings = ProductForm::where("model_name", "IndexItem")->first();
        if ($productFormSettings->dotline_form_class) {
            $form->addFormClassScript($productFormSettings->dotline_form_class);
        }
        return $form;
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $enableFiledList = isset(request()->get('routespecificpermission')['field_list']) ? request()->get('routespecificpermission')['field_list'] : [];
        $listRefidCondition = isset(request()->get('routespecificpermission')['list_refid_condition']) ? request()->get('routespecificpermission')['list_refid_condition'] : [];
        $listCustomCondition = isset(request()->get('routespecificpermission')['list_custom_condition']) ? request()->get('routespecificpermission')['list_custom_condition'] : [];
        $createIdCondition = isset(request()->get('routespecificpermission')['create_id_condition']) ? request()->get('routespecificpermission')['create_id_condition'] : [];
        $createRefidCondition = isset(request()->get('routespecificpermission')['create_refid_condition']) ? request()->get('routespecificpermission')['create_refid_condition'] : [];
        $editIdCondition = isset(request()->get('routespecificpermission')['edit_id_condition']) ? request()->get('routespecificpermission')['edit_id_condition'] : [];
        $editRefidCondition = isset(request()->get('routespecificpermission')['edit_refid_condition']) ? request()->get('routespecificpermission')['edit_refid_condition'] : [];
        $editCustomCondition = isset(request()->get('routespecificpermission')['edit_custom_condition']) ? request()->get('routespecificpermission')['edit_custom_condition'] : [];
        $deleteIdCondition = isset(request()->get('routespecificpermission')['delete_id_condition']) ? request()->get('routespecificpermission')['delete_id_condition'] : [];
        $deleteRefidCondition = isset(request()->get('routespecificpermission')['delete_refid_condition']) ? request()->get('routespecificpermission')['delete_refid_condition'] : [];
        $deleteCustomCondition = isset(request()->get('routespecificpermission')['delete_custom_condition']) ? request()->get('routespecificpermission')['delete_custom_condition'] : [];
        $actionPermission = isset(request()->get('routespecificpermission')['action_permission']) ? request()->get('routespecificpermission')['action_permission'] : [];

        $grid = new Grid(new IndexItem);

        $grid->column("id", __("Id"));
        if (empty($enableFiledList)) {

        } else {
            foreach ($enableFiledList as $column) {
                switch ($column[2]) {
                    case 'image':
                        $grid->column($column[0], __($column[1]))->image();
                        break;
                    case 'file':
                        $grid->column($column[0], __($column[1]))->file();
                        break;
                    default:
                        $grid->column($column[0], __($column[1]));
                        break;
                }
            }
        }

        $productFormSettings = ProductForm::where("model_name", "IndexItem")->first();
        $enableAdministratorPermissionByDefault = $productFormSettings->enable_administrator_permission;

        //except super user
        $gridquery = $grid->model();
        if ((Admin::user()->organization_id != 1) || ((Admin::user()->organization_id == 1) && ($enableAdministratorPermissionByDefault == '0'))) {
            $gridquery->where(function ($query) use ($listRefidCondition, $listCustomCondition) {
                $query->whereHas('creator', function ($query) use ($listRefidCondition) {
                    foreach ($listRefidCondition as $key => $condition) {
                        if (isset($condition['condition_field']) && isset($condition['condition_op']) && $condition['condition_field'] != '' && $condition['condition_op'] != '') {
                            switch ($condition['joining_rules']) {
                                case 'NA':
                                    $query->where($condition['condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . Admin::user()->{$condition['condition_field']} . "%" : Admin::user()->{$condition['condition_field']});
                                    break;
                                case 'AND':
                                    $query->where($condition['condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . Admin::user()->{$condition['condition_field']} . "%" : Admin::user()->{$condition['condition_field']});
                                    break;
                                case 'OR':
                                    $query->orWhere($condition['condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . Admin::user()->{$condition['condition_field']} . "%" : Admin::user()->{$condition['condition_field']});
                                    break;
                            }
                        }
                    }
                });
                foreach ($listCustomCondition as $key => $condition) {
                    $condition_value = (isset($condition['condition_value_auth']) && ($condition['condition_value_auth'])) ? Admin::user()->{$condition['condition_value_auth']} : $condition['condition_value'];
                    if (isset($condition['list_custom_condition_field']) && isset($condition['condition_op']) && isset($condition['condition_value']) && $condition['list_custom_condition_field'] != '' && $condition['condition_op'] != '' && $condition['condition_value'] != '') {
                        switch ($condition['joining_rules']) {
                            case 'NA':
                                $query->where($condition['list_custom_condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . $condition_value . "%" : $condition_value);
                                break;
                            case 'AND':
                                $query->where($condition['list_custom_condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . $condition_value . "%" : $condition_value);
                                break;
                            case 'OR':
                                $query->orWhere($condition['list_custom_condition_field'], $condition['condition_op'], ($condition['condition_op'] == 'like') ? "%" . $condition_value . "%" : $condition_value);
                                break;
                        }
                    }
                }

            });
        }

        $disableCreateButton = true;
        $checkCreateButtonPermission = DotlineCommon::checkCreateButtonPermission($actionPermission, $createIdCondition);
        if (($checkCreateButtonPermission) || ((Admin::user()->organization_id == 1) && ($enableAdministratorPermissionByDefault == '1'))) {
            $disableCreateButton = false;
        }
        $grid->disableCreateButton($disableCreateButton);

        $gridSetting = ($productFormSettings->grid_action_btn) ? $productFormSettings->grid_action_btn : [];
        $grid->actions(function ($actions) use ($enableAdministratorPermissionByDefault, $actionPermission, $gridSetting, $editIdCondition, $editRefidCondition, $editCustomCondition, $deleteIdCondition, $deleteRefidCondition, $deleteCustomCondition) {
            $disableViewButton = true;
            $disableEditButton = true;
            $disableDeleteButton = true;
            if (
                in_array("view", $gridSetting) && (in_array("View", $actionPermission) || ((Admin::user()->organization_id == 1) && ($enableAdministratorPermissionByDefault == '1')))
            ) {
                $disableViewButton = false;
            }

            $checkEditButtonPermission = DotlineCommon::checkEditButtonPermission($actionPermission, $editIdCondition, $editRefidCondition, $editCustomCondition, $actions->row);
            $checkDeleteButtonPermission = DotlineCommon::checkDeleteButtonPermission($actionPermission, $deleteIdCondition, $deleteRefidCondition, $deleteCustomCondition, $actions->row);

            if (in_array("edit", $gridSetting) && ($checkEditButtonPermission || ((Admin::user()->organization_id == 1) && ($enableAdministratorPermissionByDefault == '1')))) {
                $disableEditButton = false;
            }
            if (in_array("delete", $gridSetting) && ($checkDeleteButtonPermission || ((Admin::user()->organization_id == 1) && ($enableAdministratorPermissionByDefault == '1')))) {
                $disableDeleteButton = false;
            }

            $actions->disableView($disableViewButton);
            $actions->disableEdit($disableEditButton);
            $actions->disableDelete($disableDeleteButton);
            if (in_array("buy_now_btn", $gridSetting)) {
                $buyNowURL = "#";
                $actions->append('&nbsp;<a href="' . $buyNowURL . '" class="grid-row-edit" title="Buy Now"><i class="fa fa-shopping-cart"></i></a>');
            }
        });

        if (!in_array("enable_filter", $gridSetting)) {
            $grid->disableFilter();
        }
        $grid->filter(function ($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();


        });

        if ($productFormSettings->dotline_grid_class) {
            $grid->addGridClassScript($productFormSettings->dotline_grid_class);
        }
        if (in_array("enable_row_selector", $gridSetting)) {
            $grid->disableRowSelector(false);
        }
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
        $show = new Show(IndexItem::findOrFail($id));

        //$show->id("ID");
        $show->field("title", __("Item"));
        $show->field("box_id", __("Box Id"));

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
        return 'index_items';
    }


}

<?php

namespace App\Admin\Controllers;

use App\Models\Box;
use App\Models\BoxComment;
use App\Models\IndexItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use App\Models\AdminUser;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Str;

class MoveToArchiveController extends AdminController
{
    public function moveToArchiveReport(): Content
    {
        return Admin::content(function (Content $content) {
            $content->header("Archived");
            $content->description("&nbsp;");
            $content->body($this->moveToArchiveReportGrid());
        });
    }

    public function moveToArchiveReportGrid(): Grid
    {
        $grid = new Grid(new Box);
        $query = $grid->model();
        $query->where(function ($q) {
            $q->where('is_archived', 1);
            $q->where('status', 'Shred It');
        });

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
        $grid->column('status', 'Status');

        $grid->column("updated_at", __("Date"))->display(function ($updated_at) {
            return date('d/m/Y', strtotime($updated_at));
        });

        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->disableActions();

        $grid->model()->orderBy('id', 'desc');
        return $grid;
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

}

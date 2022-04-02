<?php

namespace App\Admin\Extensions;

use Encore\Admin\Grid\Exporters\ExcelExporter;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;

class BoxExporter extends ExcelExporter implements WithColumnFormatting, WithMapping, ShouldAutoSize
{
    protected $fileName = 'boxes.xlsx';

    protected $columns = [
        'id' => 'Sl',
        'serial_no' => 'Box Serial',
        'index_item' => 'Index Item',
        'box_comment' => 'Comment History',
        'status' => 'Status',
        'updated_at' => 'Date',
    ];

    // protected $headings = ['ID', 'Box Serial', 'Index Item', 'Comment History', 'Status', 'Date'];

    public function map($row): array
    {
        $indexItem = (isset($row->index_item) && !empty($row->index_item)) ? $row->index_item->toArray() : [];
        $comments = (isset($row->box_comment) && !empty($row->box_comment)) ? $row->box_comment->toArray() : [];
        return [
            $row->id,
            $row->serial_no,
            self::joiner($indexItem),
            self::joiner($comments),
            $row->status,
            $row->updated_at
        ];
    }

    public function columnFormats(): array
    {
        return [];
    }

    public static function joiner($myObj, $separator = ","): string
    {
        if (!empty($myObj)) {
            $myObj = array_map(function ($index) {
                return $index['title'] ?? "";
            }, $myObj);
            return join($separator, $myObj);
        }
        return "";
    }
}

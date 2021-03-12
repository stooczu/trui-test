<?php

namespace App\Services;

use App\Imports\ExcelDataImport;

class ExcelService
{
    public static function getData($filepath)
    {
        $sheets = (new ExcelDataImport)->toArray($filepath);
        $data = [];
        foreach ($sheets as $sheet) {
            $buffer = [];
            foreach ($sheet as $key => $row) {
                $unique = array_unique($row);
                if (count($unique) == 1 && $unique[0] == null) continue;
                foreach ($sheet[0] as $index => $header) {
                    if ($key == 0) continue;
                    else $buffer[$header][] = $row[$index];
                }
            }
            $data[] = $buffer;
        }
        return $data;
    }
    public static function normalizeExcelArray($data)
    {
        foreach ($data as &$row) {
            foreach ($row as $index =>  &$cell) {
                $exploded = explode(',', $cell);
                $exploded = array_map('trim', $exploded);
                if (count($exploded) > 1) $cell = $exploded;
            }
        }
        return $data;
    }
}

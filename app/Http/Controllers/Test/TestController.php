<?php

namespace App\Http\Controllers\Test;

use App\Services\ExcelService;
use App\Services\FtpService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ZipArchive;

class TestController extends Controller
{
    protected $storage_path = '';
    public function __construct()
    {
        $this->storage_path = storage_path('tmp');
    }
    public function getFilters()
    {
        $file_name = 'arkusz.xlsx';
        $file_path = FtpService::download(env('FTP_FILE_NAME'), $file_name, $this->storage_path);
        if (!$file_path) {
            return response()->json(['status' => 'Requested resource is currently unavailable', 'code' => 503], 503);
        }
        $data = ExcelService::getData($file_path);
        $data = $data[0] ?? [];
        $data = ExcelService::normalizeExcelArray($data);
        $filters = $this->prepareFilters($data);
        return response()->json(['filters' => $filters], 200);
    }
    public function getFiles(Request $request)
    {
        $files = $request->has('files') ? $request->get('files') : [];
        $ftp_files = [];
        $ext = '.pdf';
        foreach ($files as $file) {
            $file_path = FtpService::download($file . $ext, $file . $ext, $this->storage_path);
            if ($file_path) {
                $ftp_files[] = [
                    'path' => $file_path,
                    'name' => $file . $ext
                ];
            }
        }
        if (count($ftp_files) == 1) {
            return response()->download($ftp_files[0]['path'], $ftp_files[0]['name']);
        } else if (count($ftp_files) > 1) {
            $filename = 'files_' . date('Y-m-h-Hi') . '.zip';
            $zip_file = storage_path($filename);
            $zip = new ZipArchive();
            $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($ftp_files as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();
            return response()->download($zip_file);
        } else {
            return response()->json(['status' => 'Requested resource is currently unavailable', 'code' => 503], 503);
        }
    }
    private function prepareFilters($data)
    {
        if (count($data) == 0) return [];
        //find filters columns
        $filter_def = 'DPO_Filter:';
        $restult_file_names_column = 'file_names';
        $files_names = $data['DOK_Name FTP-Server'];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, $filter_def)) {
                $new_key = str_replace($filter_def, '', $key);
                $data[$new_key] = $value;
            }
            unset($data[$key]);
        }
        //remove empty columns
        foreach ($data as $key => $column) {
            $nulls_count = count(array_filter($column, function ($cell) {
                return $cell == null;
            }));
            if ($nulls_count == count($column)) unset($data[$key]);
        }
        //find arrays indexes inside data
        $arrays_indexes = [];
        foreach ($data as $key => $row) {
            foreach ($row as $index => $cell) {
                if (is_array($cell)) {
                    $arrays_indexes[] = ['index' => $index, 'count' => count($cell)];
                }
            }
        }
        //clone rows found arrays indexes
        $new_data = [];
        $indexes_to_copy = array_column($arrays_indexes, 'index');
        $data[$restult_file_names_column] = $files_names;
        foreach ($data as $key => $row) {
            foreach ($row as $index => $cell) {
                $new_data[$key][] = $cell;
                $index_to_copy = array_search($index, $indexes_to_copy);
                if (is_int($index_to_copy)) {
                    $count = $arrays_indexes[$index_to_copy]['count'];
                    for ($i = 1; $i < $count; $i++) {
                        $new_data[$key][] = $cell;
                    }
                }
            }
        }
        //replace arrays with array fields
        $data = $new_data;
        foreach ($data as $key => &$row) {
            if ($key == $restult_file_names_column) continue;
            foreach ($row as $index => &$cell) {
                if (is_array($cell)) {
                    array_splice($row, $index, count($cell), $cell);
                }
            }
        }
        $rows = [];
        //map data
        foreach ($data as $name => &$row) {
            $files_columns = $name == $restult_file_names_column;
            $options = [];
            $new_value = null;
            foreach ($row as $index => &$cell) {
                if ($cell != $new_value) {
                    $new_value = $cell;
                    $options[] = [
                        'name' => $cell,
                        'key' => $index
                    ];
                }
            }
            $rows[] = [
                'name' => $name,
                'options_file_names' => $files_columns,
                'options' => array_values($options),
            ];
        }
        return $rows;
    }
}

<?php
namespace App\Services;

use App\Exports\SingleSheetExport;
use App\Exports\MultipleSheetExport;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

define('LIMIT', 50000);

class ExcelService {

    public static function exportExcel(array $header, array $data, string $name = "Export", $path = "", $filename = "export.xlsx") {
        $data_count = count($data);
        if ($data_count < LIMIT) {
            if ($path == "") {
                return Excel::download(new SingleSheetExport($header, $data, $name), $filename);
            }
            // dd(public_path().'/'.$path);
            return (new SingleSheetExport($header, $data, $name))->store($path, "excel_local");
        } else {
            $finaldata = [];
            $collectionOfData = collect($data);
            $splitCollections = $collectionOfData->splitIn($data_count/LIMIT)->toArray();
            foreach ($splitCollections as $value) {
                $sheet  = array( 0 => $header, 1 => $value);
                $finaldata[] = $sheet;
            }
            // dd($finaldata);
            if ($path == "") {
                return Excel::download(new MultipleSheetExport($finaldata, $name), $filename);
            }
            return (new MultipleSheetExport($finaldata, $name))->store($path, "excel_local");
        }
    }

    public static function sortArrayKeysPair(array $data) {
        $header = [];
        $row = [];
        foreach($data[0] as $key => $transaction) {
            $header[] = $key;
        }
        foreach($data as $transaction) {
            $inData = [];
            foreach($transaction as $key => $transactionData) {
                $inData[] = $transactionData;
            }
            $row[]= $inData;
        }
        // dd($row);
        return ["header" => $header, "row" => $row];
    }


    public static function fastExcelExport(array $data, string $name = "Export", $path = "", $filename = "export.xlsx"){
        $data_count = count($data);
        if ($data_count < LIMIT) {
            if ($path == "") {
                return (new FastExcel(collect($data)))->download($filename);
            }
            return (new FastExcel(collect($data)))->export($path);
        } else {
            
            $tempSheets = [];
            $i = 0;
            foreach (array_chunk($data, LIMIT) as $dataIn) {
                $i++;
                $tempSheets[" Sheet " . $i] = $dataIn;
            }
             
            $sheets = new SheetCollection($tempSheets);
            if ($path == "") {
                return (new FastExcel($sheets))->download($filename);
            }
            return (new FastExcel($sheets))->export( $path);
        }
    }

    public static function fastExcelMultipleExport(array $data, string $name = "Export", $path = "", $filename = "export.xlsx") {
        $tempSheets = [];
        $i = 0;
        foreach ($data as $key => $dataIn) {
            $i++;
            $tempSheets[$key] = $dataIn;
        }
            
        $sheets = new SheetCollection($tempSheets);
        if ($path == "") {
            return (new FastExcel($sheets))->download($filename);
        }
        return (new FastExcel($sheets))->export( $path);
    }

    public static function fastExcelImport($file){
        $sheets = (new FastExcel)->importSheets($file)->toArray();
        $count = count($sheets);
        $temp = [];
        for ($i=0; $i < $count; $i++) { 
            $temp = [...$temp, ...$sheets[$i]];
        }
         return $temp;
    }
}

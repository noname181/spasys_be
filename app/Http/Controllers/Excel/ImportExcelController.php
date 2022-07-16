<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Excel\ExcelRequest;
use App\Models\Excel;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportExcelController extends Controller
{
    /**
     * Fetch data
     * @param  
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            $f = Storage::disk('public')->put('files', $request['file']);

            $path = storage_path('app/public') . '/' . $f;
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);

            $errors = [];
            $results = [];
            $excel = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $datas = $sheet->toArray(null, true, true, true);
                $results[$sheet->getTitle()] = [];
                $errors[$sheet->getTitle()] = [];
                foreach ($datas as $key => $d) {
                    $validator = Validator::make($d, ExcelRequest::rules());
                    if ($validator->fails()) {
                        $errors[$sheet->getTitle()][] = [
                            $key =>  $validator->errors()
                        ];
                    } else {
                        $results[$sheet->getTitle()][] = $d;
                        $excel[] = [
                            'a' => $d['A'],
                            'b' => $d['B'],
                            'c' => $d['C'],
                            'd' => $d['D'],
                        ];
                    }
                }
            }
            Excel::insert($excel);

            return response()->json(compact('results', 'errors'));
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (null !== $cell) return false;
        }
        return true;
    }
}


// Run exec create table
// CREATE TABLE `spasys1`.`excel` (
//     `excel_no` INT NOT NULL AUTO_INCREMENT,
//     `a` VARCHAR(45) NULL,
//     `b` VARCHAR(45) NULL,
//     `c` VARCHAR(45) NULL,
//     `d` VARCHAR(45) NULL,
//     PRIMARY KEY (`excel_no`));
  
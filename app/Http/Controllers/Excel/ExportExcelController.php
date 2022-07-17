<?php

namespace App\Http\Controllers\Excel;

use App\Utils\Messages;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportExcelController extends Controller
{
    public function exportExcel()

    {
        try{
            $productlist = [
                0 => [
                    "product_id" => 'A001',
                    "product_name" => "Tivi",
                    "product_quantity" => 1,
                    'product_price' => 1000,
                ],
                1 => [
                    "product_id" => 'A002',
                    "product_name" => "Binh Hoa",
                    "product_quantity" => 1,
                    'product_price' => 3000,
                ]
            ];

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="sample.xlsx"');
            header('Cache-Control: max-age=0');
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: no-cache, private');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
            $sheet->setCellValue('A1', 'S.No');
            $sheet->setCellValue('B1', 'Product Name');
            $sheet->setCellValue('C1', 'Quantity');
            $sheet->setCellValue('D1', 'Price');

            $sn = 2;
            foreach ($productlist as $prod) {
                $sheet->setCellValue('A' . $sn, $prod['product_id']);
                $sheet->setCellValue('B' . $sn, $prod['product_name']);
                $sheet->setCellValue('C' . $sn, $prod['product_quantity']);
                $sheet->setCellValue('D' . $sn, $prod['product_price']);
                $sn++;
            }

            $writer = new Xlsx($spreadsheet);
            ob_end_clean();
            $writer->save('php://output');
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}

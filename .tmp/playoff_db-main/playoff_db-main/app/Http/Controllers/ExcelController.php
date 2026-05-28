<?php
//namespace App\Http\Controllers;
//use App\Models\panini_mlb_player;
//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//use PhpOffice\PhpSpreadsheet\IOFactory;
//use Illuminate\Http\Request;
//
//class ExcelController extends Controller
//{
//    public function filterPlayers(Request $request)
//    {
//        $filePath = storage_path('app/public/fitel_mlb.xlsx');
//        $spreadsheet = IOFactory::load($filePath);
//        $sheet = $spreadsheet->getActiveSheet();
//        $names = $sheet->toArray(null, true, true, true);
//        $existingPlayers = panini_mlb_player::pluck('player')->toArray();
//        $remainingNames = [];
//        foreach ($names as $row) {
//            $playerName = $row['A'];
//            if (!in_array($playerName, $existingPlayers) && !empty($playerName)) {
//                $remainingNames[] = $playerName;
//            }
//        }
//        $newSpreadsheet = new Spreadsheet();
//        $newSheet = $newSpreadsheet->getActiveSheet();
//        $rowIndex = 1;
//        foreach ($remainingNames as $name) {
//            $newSheet->setCellValue('A' . $rowIndex, $name);
//            $rowIndex++;
//        }
//        $newFilePath = storage_path('app/public/filtered_mlb_players.xlsx');
//        $writer = new Xlsx($newSpreadsheet);
//        $writer->save($newFilePath);
//        return response()->download($newFilePath)->deleteFileAfterSend(true);
//    }
//}
//
namespace App\Http\Controllers;
use App\Models\panini_mlb_player;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;

class ExcelController extends Controller
{
    public function filterPlayers(Request $request)
    {
        // Đường dẫn đến file Excel
        $filePath = storage_path('app/public/filtered_mlb_players.xlsx');

        // Đọc file Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Lấy tất cả dữ liệu từ file Excel
        $rows = $sheet->toArray(null, true, true, true);

        // Lấy tất cả các tên cầu thủ hiện có trong cơ sở dữ liệu
        $existingPlayers = panini_mlb_player::pluck('player')->toArray();

        // Tạo một bảng tính mới để ghi dữ liệu đã lọc
        $newSpreadsheet = new Spreadsheet();
        $newSheet = $newSpreadsheet->getActiveSheet();

        $rowIndex = 1; // Khởi tạo chỉ số hàng cho bảng tính mới

        // Duyệt qua từng hàng trong file Excel
        foreach ($rows as $row) {
            $playerName = $row['A']; // Giả sử tên cầu thủ nằm ở cột A

            // Nếu tên cầu thủ không có trong cơ sở dữ liệu, ghi hàng đó vào bảng tính mới
            if (!in_array($playerName, $existingPlayers) && !empty($playerName)) {
                $newSheet->fromArray($row, null, 'A' . $rowIndex);
                $rowIndex++;
            }
        }

        // Lưu file Excel mới
        $newFilePath = storage_path('app/public/filtered_mlb_players.xlsx');
        $writer = new Xlsx($newSpreadsheet);
        $writer->save($newFilePath);

        // Trả file Excel đã lọc để tải về
        return response()->download($newFilePath)->deleteFileAfterSend(true);
    }
}

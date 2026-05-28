<?php

namespace App\Services\Program;


class ChangeJSON
{
    public function changeToJson($data)
    {
        // Loại bỏ dấu ngoặc tròn
        $text = str_replace(['(', ')'], '', $data);

        // Tách chuỗi theo dấu "+"
        $elements = preg_split('/\s*\+\s*/', $text);

        // Thêm dấu cách vào cuối mỗi phần tử (nếu cần)
        $json = array_map(fn($item) => trim($item) . ' ', $elements);
        //chuyển thành json
        $datas = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $datas;
    }

    public function removeParentheses($input)
    {
        // Loại bỏ nội dung trong ngoặc đơn và cả ngoặc đơn
        return preg_replace('/\s*\(.*?\)\s*/', ' ', $input);
    }
}

<?php

namespace App\Services\Program;

use Illuminate\Support\Facades\Log;
use App\Http\Livewire\Traits\MenuNavArray;
use App\Http\Livewire\Traits\PressColorArray;

class ProgramSubFormDetailServices
{

    use MenuNavArray;
    use PressColorArray;

    /**
     * Tách dữ liệu prepress thành mảng
     *
     * @param array $data
     * @return array
     */


    public function splitPrepressData($data)
    {
        $result = [];
        foreach ($this->pressColorGroupArray() as $group) {
            if ($group['front'] == 1) {
                for ($i = 0; $i <= $group['qty']; $i++) {
                    if ($group['numInFront'] == 0) {
                        $result[$group['shortName']][] = [
                            'shortName' => "{$group['shortName']}{$i}",
                            'name' => $group['name'],
                            'checkbox' => $i,
                            'status' => $group['status'],
                            'qty' => $group['qty'],
                            'numInFront' => $group['numInFront'],
                            'start' => $group['start'],

                        ];
                    } else {
                        $result[$group['shortName']][] = [
                            'shortName' => "{$i}{$group['shortName']}",
                            'name' => $group['name'],
                            'checkbox' => $i,
                            'status' => $group['status'],
                            'qty' => $group['qty'],
                            'numInFront' => $group['numInFront'],
                            'start' => $group['start'],
                        ];
                    }
                }
                if (!empty($group['randomNum'])) {
                    foreach ($group['randomNum'] as $random) {
                        if ($group['numInFront'] == 0) {
                            $result[$group['shortName']][] = [
                                'shortName' => "{$group['shortName']}{$random}",
                                'name' => $group['name'],
                                'checkbox' => $random,
                                'status' => $group['status'],
                                'qty' => $group['qty'],
                                'numInFront' => $group['numInFront'],
                                'start' => $group['start'],
                            ];
                        } else {
                            $result[$group['shortName']][] = [
                                'shortName' => "{$random}{$group['shortName']}",
                                'name' => $group['name'],
                                'checkbox' => $random,
                                'status' => $group['status'],
                                'qty' => $group['qty'],
                                'numInFront' => $group['numInFront'],
                                'start' => $group['start'],
                            ];
                        }
                    }
                }
            }
        }

        // Duyệt qua dữ liệu từ prepress_color_front_json
        foreach ($data as $item) {
            if (preg_match('/^(\d+)(THK|THK_B)$/', $item, $matches)) {
                $group = $matches[2]; // Tên nhóm
                $checkboxIndex = isset($matches[1]) ? (int)$matches[1] : 0;

                if (isset($result[$group][$checkboxIndex])) {
                    $result[$group][$checkboxIndex]['status'] = 1;
                }
            } else {
                preg_match('/^(.*?)(\d+)?$/', $item, $matches);
                if (!empty($matches)) {
                    $group = $matches[1]; // Tên nhóm
                    $checkboxIndex = isset($matches[2]) ? (int)$matches[2] : 0;

                    if (isset($result[$group][$checkboxIndex])) {
                        $result[$group][$checkboxIndex]['status'] = 1;
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Tách dữ liệu prepress back thành mảng
     *
     * @param array $data
     * @return array
     */

    public function splitPrepressDataBack($data)
    {
        // Danh sách nhóm cố định

        $result = [];
        foreach ($this->pressColorGroupArray() as $group) {
            if ($group['back'] == 1) {
                for ($i = 0; $i <= $group['qty']; $i++) {
                    if ($group['numInFront'] == 0) {
                        $result[$group['shortName']][] = [
                            'shortName' => "{$group['shortName']}{$i}",
                            'checkbox' => $i,
                            'status' => $group['status'],
                            'qty' => $group['qty'],
                            'name' => $group['name'],
                            'numInFront' => $group['numInFront'],
                            'start' => $group['start'],
                        ];
                    } else {
                        $result[$group['shortName']][] = [
                            'shortName' => "{$i}{$group['shortName']}",
                            'checkbox' => $i,
                            'status' => $group['status'],
                            'qty' => $group['qty'],
                            'name' => $group['name'],
                            'numInFront' => $group['numInFront'],
                            'start' => $group['start'],
                        ];
                    }
                }
                if (!empty($group['randomNum'])) {
                    foreach ($group['randomNum'] as $random) {
                        if ($group['numInFront'] == 0) {
                            $result[$group['shortName']][] = [
                                'shortName' => "{$group['shortName']}{$random}",
                                'checkbox' => $random,
                                'status' => $group['status'],
                                'qty' => $group['qty'],
                                'name' => $group['name'],
                                'numInFront' => $group['numInFront'],
                                'start' => $group['start'],
                            ];
                        } else {
                            $result[$group['shortName']][] = [
                                'shortName' => "{$random}{$group['shortName']}",
                                'checkbox' => $random,
                                'status' => $group['status'],
                                'qty' => $group['qty'],
                                'name' => $group['name'],
                                'numInFront' => $group['numInFront'],
                                'start' => $group['start'],
                            ];
                        }
                    }
                }
            }
        }

        // Duyệt qua dữ liệu từ prepress_color_front
        foreach ($data as $item) {

            if (preg_match('/^(\d+)(THK|THK_B)$/', $item, $matches)) {
                $group = $matches[2]; // Tên nhóm
                $checkboxIndex = isset($matches[1]) ? (int)$matches[1] : 0;

                if (isset($result[$group][$checkboxIndex])) {
                    $result[$group][$checkboxIndex]['status'] = 1;
                }
            } else {
                preg_match('/^(.*?)(\d+)?$/', $item, $matches);
                if (!empty($matches)) {
                    $group = $matches[1]; // Tên nhóm
                    $checkboxIndex = isset($matches[2]) ? (int)$matches[2] : 0;

                    if (isset($result[$group][$checkboxIndex])) {
                        $result[$group][$checkboxIndex]['status'] = 1;
                    }
                }
            }
        }

        return $result;
    }

    public function processArrayData($dataArray)
    {
        if (empty($dataArray)) {
            return [];
        }
        // Xử lý từng phần tử trong mảng
        $result = array_reduce($dataArray, function ($carry, $data) {
            // Loại bỏ các ký tự không cần thiết
            $cleanedData = str_replace(['(', ')'], '', $data);
            // Gộp vào mảng kết quả
            return array_merge($carry, $cleanedData);
        }, []);

        return $result;
    }

    public function splitData($data)
    {
        if (!$data) {
            return [];
        }
        // Loại bỏ các ký tự không cần thiết
        $items = str_replace(['(', ')', '[', ']', '"'], '', $data);
        $items = array_map('trim', explode(',', $items));
        // Tách các mục
        return $items;
    }
}

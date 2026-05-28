<?php

namespace App\Http\Livewire\Traits;

/**
 * 提供印刷顏色組相關功能
 */
trait PressColorArray
{
    /**
     * 獲取所有印刷顏色組配置
     * 
     * @return array 顏色組配置數組，每個元素包含以下鍵：
     *   - name: 顏色組名稱
     *   - shortName: 顏色組簡稱
     *   - numInFront: 前面的數字
     *   - start: 起始值
     *   - status: 狀態 (0: 未啟用, 1: 啟用)
     *   - randomNum: 隨機數數組
     *   - qty: 數量
     *   - front: 是否用於正面 (1: 是, 0: 否)
     *   - back: 是否用於背面 (1: 是, 0: 否)
     */
    public function pressColorGroupArray()
    {
        return [
            [
                "name" => "4/C",
                "shortName" => "4/C",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "1/C",
                "shortName" => "1/C",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "EE",
                "shortName" => "EE",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "SOFT-TOUCH",
                "shortName" => "S-TOUCH",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "SCRWHT",
                "shortName" => "SCWHT",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "WHT",
                "shortName" => "WHT",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 3,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "K",
                "shortName" => "#K",
                "numInFront" => 1,
                "start" => 5,
                "status" => 0,
                "randomNum" => [],
                "qty" => 20,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "K_B",
                "shortName" => "#K_B",
                "numInFront" => 1,
                "start" => 5,
                "status" => 0,
                "randomNum" => [],
                "qty" => 20,
                "front" => 0,
                "back" => 1
            ],
            [
                "name" => "ETCH",
                "shortName" => "ETCH",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 30,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "PMS",
                "shortName" => "PMS",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 27,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "FOIL",
                "shortName" => "FOIL",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 30,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "PREFOIL",
                "shortName" => "PFOIL",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 5,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "MICROETCH",
                "shortName" => "MICROETCH",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 8,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "DIE",
                "shortName" => "DIE",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 18,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "EMBOSS",
                "shortName" => "EMBOSS",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "FRAME-DIE",
                "shortName" => "FRAME-DIE",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "WHTOP",
                "shortName" => "WHTOP",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 1,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "STK",
                "shortName" => "STK",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "MM",
                "shortName" => "MM",
                "numInFront" => 0,
                "start" => 101,
                "status" => 0,
                "randomNum" => [213, 248, 507, 511, 607, 701,],
                "qty" => 131,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "SPOTTER",
                "shortName" => "SPOTTER",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 36,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "SPOTUV",
                "shortName" => "SPUV",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 3,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "SEQ",
                "shortName" => "SEQ",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 2,
                "front" => 1,
                "back" => 1
            ],
            [
                "name" => "K",
                "shortName" => "K#",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 2,
                "front" => 0,
                "back" => 1
            ],
            [
                "name" => "PDIE",
                "shortName" => "PDIE",
                "numInFront" => 0,
                "start" => 1,
                "status" => 0,
                "randomNum" => [],
                "qty" => 5,
                "front" => 1,
                "back" => 0
            ],
            [
                "name" => "PMCDIE",
                "shortName" => "PMCDIE",
                "numInFront" => 0,
                "start" => 0,
                "status" => 0,
                "randomNum" => [],
                "qty" => 0,
                "front" => 1,
                "back" => 0
            ],
        ];
    }
}

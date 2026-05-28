<div>
    <div class="">
        <div class="">
            <div class="flex justify-between px-5 pt-5 " style="background-color: {{$player->team_color()}}">
                <div class="flex-1 h-56 relative">
                    <div class="absolute top-0 left-0">
                        <img class="rounded-sm h-24" src="{{$player->team_icon("D")}}" alt="">
                    </div>
                    <div class="absolute bottom-0 left-8">
                        <img class="rounded-sm h-48" src="{{$player->player_image_url()}}" alt="">
                    </div>
                </div>
                <div class="flex-1 flex-col justify-center items-center">
                    <div class="flex space-x-3">
                        <div class="text-white text-xl">{{$player->position!=null?$player->position:''}}</div>
                        <div
                                class="text-white text-xl">{{$player->jersey_number!=null?'#'.$player->jersey_number:''}}</div>
                    </div>
                    <div class="text-white text-4xl font-bold">{{$player->player}}</div>
                    <div class="text-white text-xl">{{$player->team_name}}</div>
                </div>
                <div class="flex flex-1 justify-center items-center">
                    <a href="https://www.nba.com/stats/player/{{$player->nba_player_id}}/career?PerMode=Totals"
                       target="_blank"
                       class="border text-gray-100 hover:bg-blue-500 hover:text-white active:bg-blue-600 font-bold uppercase text-xs px-4 py-2 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150"
                       type="button">
                        NBA Page
                    </a>
                    @if($player->bb_ref !== null)
                        <a href="{{$player->bb_ref}}" target="_blank"
                           class="border text-gray-100 hover:bg-blue-500 hover:text-white active:bg-blue-600 font-bold uppercase text-xs px-4 py-2 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150"
                           type="button">
                            BasketBall Ref
                        </a>
                    @endif
                </div>
            </div>

            @php
                // 确保career_stats是数组
                if (isset($player->career_stats) && is_string($player->career_stats)) {
                    $player->career_stats = json_decode($player->career_stats, true);
                }

                // 确保last_year_career_stats是数组
                if (isset($player->last_year_career_stats) && is_string($player->last_year_career_stats)) {
                    $player->last_year_career_stats = json_decode($player->last_year_career_stats, true);
                }

                // 确保college_career_stats是数组
                 if (isset($player->college_career_stats) && is_string($player->college_career_stats)) {
                    $player->college_career_stats = json_decode($player->college_career_stats, true);
                }

                // 统一键名，确保大小写兼容
                $careerKeys = [
                    'G' => ['G', 'g'],
                    'FG%' => ['FG%', 'fg%'],
                    'FT%' => ['FT%', 'ft%'],
                    '3PM' => ['3PM', '3pm'],
                    'RPG' => ['RPG', 'rpg'],
                    'APG' => ['APG', 'apg'],
                    'STL' => ['STL', 'stl'],
                    'BLK' => ['BLK', 'blk'],
                    'PTS' => ['PTS', 'pts'],
                    'PPG' => ['PPG', 'ppg']
                ];

                // 获取统计数据函数
                if (!function_exists('getStat')) {
                    function getStat($stats, $key, $keys) {
                        if (!is_array($stats)) {
                             return null; // 如果 $stats 不是陣列，返回 null
                        }
                        foreach ($keys[$key] as $k) {
                            if (isset($stats[$k])) {
                                $value = $stats[$k];
                                // 確保返回的是數字類型，如果是字符串數字則轉換
                                if (is_numeric($value)) {
                                    return is_string($value) ? (float)$value : $value;
                                }
                                return $value;
                            }
                        }
                        return null;
                    }
                }

                // 安全的數字格式化函數
                if (!function_exists('safeNumberFormat')) {
                    function safeNumberFormat($value, $decimals = 0) {
                        if ($value === null || !is_numeric($value)) {
                            return '-';
                        }
                        $num = is_string($value) ? (float)$value : $value;
                        return number_format($num, $decimals);
                    }
                }

                // 百分比格式化函數
                if (!function_exists('formatPercentage')) {
                    function formatPercentage($value) {
                        if ($value === null || !is_numeric($value)) {
                            return '-';
                        }
                        $num = is_string($value) ? (float)$value : $value;
                        $formatted = number_format($num, 3);
                        // 如果數值小於1且大於0，移除開頭的0
                        if ($num < 1 && $num > 0) {
                            return substr($formatted, 1);
                        }
                        return $formatted;
                    }
                }
            @endphp

            @if($player->stats->count())
                <div class="text-center font-bold text-gray-500 text-2xl py-5">NBA Stats</div>
                <div class="p-6 mt-5">
                    <table class="table-auto w-full text-gray-500">
                        <thead>
                        <tr class="text-right">
                            <th class="text-left">Year</th>
                            <th class="text-left">Team</th>
                            <th>G</th>
                            <th>FG%</th>
                            <th>FT%</th>
                            <th>3PM</th>
                            <th>RPG</th>
                            <th>APG</th>
                            <th>STL</th>
                            <th>BLK</th>
                            <th>PTS</th>
                            <th>PPG</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($player->stats as $stat)
                            @if(!$stat->extra)
                                <tr class="hover:bg-slate-100 text-right">
                                    <td class="text-left">{{$stat->year}}</td>
                                    <td class="text-left">{{$stat->team_full ?? $stat->team}}</td>
                                    <td>{{$stat->g}}</td>
                                    <td>{{ $stat->{'fg%'}!=='-'? ($stat->{'fg%'} < 1 && $stat->{'fg%'} > 0 ? substr(number_format($stat->{'fg%'}, 3), 1) : number_format($stat->{'fg%'}, 3)) :'-' }}</td>
                                    <td>{{ $stat->{'ft%'}!=='-'? ($stat->{'ft%'} < 1 && $stat->{'ft%'} > 0 ? substr(number_format($stat->{'ft%'}, 3), 1) : number_format($stat->{'ft%'}, 3)) :'-' }}</td>
                                    <td>{{$stat->{'3pm'} }}</td>
                                    <td>{{ $stat->rpg!=='-'? ($stat->rpg < 1 && $stat->rpg > 0 ? substr(number_format($stat->rpg, 1), 1) : number_format($stat->rpg, 1)) :'-' }}</td>
                                    <td>{{ $stat->apg!=='-'? ($stat->apg < 1 && $stat->apg > 0 ? number_format($stat->apg, 1) : number_format($stat->apg, 1)) :'-' }}</td>
                                    <td>{{$stat->stl}}</td>
                                    <td>{{$stat->blk}}</td>
                                    <td>{{$stat->pts}}</td>
                                    <td>{{ $stat->ppg!=='-'? ($stat->ppg < 1 && $stat->ppg > 0 ? number_format($stat->ppg, 1) : number_format($stat->ppg, 1)) :'-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                        <tfoot>
                        @if($player->career_stats)
                        <tr class="text-right">
                            <td class="text-left font-bold">Career</td>
                            <td class="text-left"></td>
                            <td>{{ getStat($player->career_stats, 'G', $careerKeys) ?? '-' }}</td>
                            <td>{{ formatPercentage(getStat($player->career_stats, 'FG%', $careerKeys)) }}</td>
                            <td>{{ formatPercentage(getStat($player->career_stats, 'FT%', $careerKeys)) }}</td>
                            <td>{{ getStat($player->career_stats, '3PM', $careerKeys) ?? '-' }}</td>
                            <td>{{ safeNumberFormat(getStat($player->career_stats, 'RPG', $careerKeys), 1) }}</td>
                            <td>{{ safeNumberFormat(getStat($player->career_stats, 'APG', $careerKeys), 1) }}</td>
                            <td>{{ getStat($player->career_stats, 'STL', $careerKeys) ?? '-' }}</td>
                            <td>{{ getStat($player->career_stats, 'BLK', $careerKeys) ?? '-' }}</td>
                            <td>{{ getStat($player->career_stats, 'PTS', $careerKeys) ?? '-' }}</td>
                            <td>{{ safeNumberFormat(getStat($player->career_stats, 'PPG', $careerKeys), 1) }}</td>
                        </tr>
                        @endif
                        @if($player->last_year_career_stats)
                        <tr class="text-right bg-gray-100">
                            <td class="text-left font-bold">Last Year Career</td>
                            <td class="text-left"></td>
                            <td>{{ getStat($player->last_year_career_stats, 'G', $careerKeys) ?? '-' }}</td>
                            <td>{{ formatPercentage(getStat($player->last_year_career_stats, 'FG%', $careerKeys)) }}</td>
                            <td>{{ formatPercentage(getStat($player->last_year_career_stats, 'FT%', $careerKeys)) }}</td>
                            <td>{{ getStat($player->last_year_career_stats, '3PM', $careerKeys) ?? '-' }}</td>
                            <td>{{ safeNumberFormat(getStat($player->last_year_career_stats, 'RPG', $careerKeys), 1) }}</td>
                            <td>{{ safeNumberFormat(getStat($player->last_year_career_stats, 'APG', $careerKeys), 1) }}</td>
                            <td>{{ getStat($player->last_year_career_stats, 'STL', $careerKeys) ?? '-' }}</td>
                            <td>{{ getStat($player->last_year_career_stats, 'BLK', $careerKeys) ?? '-' }}</td>
                            <td>{{ getStat($player->last_year_career_stats, 'PTS', $careerKeys) ?? '-' }}</td>
                            <td>{{ safeNumberFormat(getStat($player->last_year_career_stats, 'PPG', $careerKeys), 1) }}</td>
                        </tr>
                        @endif
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-6 mt-5 flex justify-center items-center h-56">
                    <div class="text-4xl text-gray-400">No data</div>
                </div>
            @endif
            @if($player->college_stats->count())
                <div class="text-center font-bold text-gray-500 text-2xl py-5">College Stats</div>
                <div class="p-6 mt-5">
                    <table class="table-auto w-full text-gray-500">
                        <thead>
                        <tr class="text-right">
                            <th class="text-left">Year</th>
                            <th class="text-left">Team</th>
                            <th>G</th>
                            <th>FG%</th>
                            <th>FT%</th>
                            <th>3PM</th>
                            <th>RPG</th>
                            <th>APG</th>
                            <th>STL</th>
                            <th>BLK</th>
                            <th>PTS</th>
                            <th>PPG</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($player->college_stats as $college_stats)
                            <tr class="hover:bg-slate-100 text-right">
                                <td class="text-left">{{$college_stats->year}}</td>
                                <td class="text-left">{{$college_stats->team}}</td>
                                <td>{{$college_stats->g}}</td>
                                <td>{{ $college_stats->{'fg%'}!=='-'? ($college_stats->{'fg%'} < 1 && $college_stats->{'fg%'} > 0 ? substr(number_format($college_stats->{'fg%'}, 3), 1) : number_format($college_stats->{'fg%'}, 3)) :'-' }}</td>
                                <td>{{ $college_stats->{'ft%'}!=='-'? ($college_stats->{'ft%'} < 1 && $college_stats->{'ft%'} > 0 ? substr(number_format($college_stats->{'ft%'}, 3), 1) : number_format($college_stats->{'ft%'}, 3)) :'-' }}</td>
                                <td>{{$college_stats->{'3pm'} }}</td>
                                <td>{{ $college_stats->rpg!=='-'? ($college_stats->rpg < 1 && $college_stats->rpg > 0 ? substr(number_format($college_stats->rpg, 1), 1) : number_format($college_stats->rpg, 1)) :'-' }}</td>
                                <td>{{ $college_stats->apg!=='-'? ($college_stats->apg < 1 && $college_stats->apg > 0 ? substr(number_format($college_stats->apg, 1), 1) : number_format($college_stats->apg, 1)) :'-' }}</td>
                                <td>{{$college_stats->stl}}</td>
                                <td>{{$college_stats->blk}}</td>
                                <td>{{$college_stats->pts}}</td>
                                <td>{{ $college_stats->ppg!=='-'? ($college_stats->ppg < 1 && $college_stats->ppg > 0 ? number_format($college_stats->ppg, 1) : number_format($college_stats->ppg, 1)) :'-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        @if($player->college_career_stats)
                            <tfoot>
                            <tr class="text-right">
                                <td class="text-left font-bold">College Career</td>
                                <td class="text-left"></td>
                                <td>{{ getStat($player->college_career_stats, 'G', $careerKeys) ?? '-' }}</td>
                                <td>{{ formatPercentage(getStat($player->college_career_stats, 'FG%', $careerKeys)) }}</td>
                                <td>{{ formatPercentage(getStat($player->college_career_stats, 'FT%', $careerKeys)) }}</td>
                                <td>{{ getStat($player->college_career_stats, '3PM', $careerKeys) ?? '-' }}</td>
                                <td>{{ safeNumberFormat(getStat($player->college_career_stats, 'RPG', $careerKeys), 1) }}</td>
                                <td>{{ safeNumberFormat(getStat($player->college_career_stats, 'APG', $careerKeys), 1) }}</td>
                                <td>{{ getStat($player->college_career_stats, 'STL', $careerKeys) ?? '-' }}</td>
                                <td>{{ getStat($player->college_career_stats, 'BLK', $careerKeys) ?? '-' }}</td>
                                <td>{{ getStat($player->college_career_stats, 'PTS', $careerKeys) ?? '-' }}</td>
                                <td>{{ safeNumberFormat(getStat($player->college_career_stats, 'PPG', $careerKeys), 1) }}</td>
                            </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>

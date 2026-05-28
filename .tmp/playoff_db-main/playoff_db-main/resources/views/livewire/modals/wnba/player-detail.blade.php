<div>
    <div class="">
        <div class="">
            <div class="flex justify-between px-5 pt-5">
                <div class="flex-1 flex-col justify-center items-center">
                    <div class="flex space-x-3">
                        <div class="text-gray-500 text-xl">{{$player->full_pos}}</div>
                    </div>
                    <div class="text-gray-500 text-4xl font-bold">{{$player->player}}</div>
                    <div class="text-gray-500 text-xl">{{$player->team}}</div>
                </div>
                <div class="flex-1 flex-col justify-center items-center">
                    <div class="text-gray-500 text-xl">Status: {{$player->status}}</div>
                    <div class="text-gray-500 text-xl">Panini ID: {{$player->panini_id}}</div>
                </div>
            </div>


            @if($player->hasStats() || $player->hasCareerStats())
                <div class="text-center font-bold text-gray-500 text-2xl py-5">WNBA Stats</div>
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
                            @if($player->hasStats())
                                @php
                                    $stats = $player->stat;
                                    // 如果是多季數據陣列，按年份排序
                                    if (isset($stats[0]) && is_array($stats[0])) {
                                        // 多季數據
                                        usort($stats, function($a, $b) {
                                            $yearA = isset($a['season']) ? (int)substr($a['season'], 0, 4) : 0;
                                            $yearB = isset($b['season']) ? (int)substr($b['season'], 0, 4) : 0;
                                            return $yearB - $yearA; // 降序排列，最新年份在前
                                        });
                                        $statsList = $stats;
                                    } else {
                                        // 單季數據
                                        $statsList = [$stats];
                                    }
                                @endphp
                                
                                @foreach($statsList as $seasonStat)
                                    <tr class="text-right">
                                        <td class="text-left">{{ $seasonStat['season'] ?? '-' }}</td>
                                        <td class="text-left">{{ $seasonStat['team'] ?? '-' }}</td>
                                        <td>{{ $seasonStat['G'] ?? '-' }}</td>
                                        <td>{{ isset($seasonStat['FG%']) && $seasonStat['FG%'] !== '-' ? ($seasonStat['FG%'] < 1 && $seasonStat['FG%'] > 0 ? substr(number_format($seasonStat['FG%'], 3), 1) : number_format($seasonStat['FG%'], 3)) : '-' }}</td>
                                        <td>{{ isset($seasonStat['FT%']) && $seasonStat['FT%'] !== '-' ? ($seasonStat['FT%'] < 1 && $seasonStat['FT%'] > 0 ? substr(number_format($seasonStat['FT%'], 3), 1) : number_format($seasonStat['FT%'], 3)) : '-' }}</td>
                                        <td>{{ $seasonStat['3PM'] ?? '-' }}</td>
                                        <td>{{ isset($seasonStat['RPG']) && $seasonStat['RPG'] !== '-' ? ($seasonStat['RPG'] < 1 && $seasonStat['RPG'] > 0 ? substr(number_format($seasonStat['RPG'], 1), 1) : number_format($seasonStat['RPG'], 1)) : '-' }}</td>
                                        <td>{{ isset($seasonStat['APG']) && $seasonStat['APG'] !== '-' ? ($seasonStat['APG'] < 1 && $seasonStat['APG'] > 0 ? substr(number_format($seasonStat['APG'], 1), 1) : number_format($seasonStat['APG'], 1)) : '-' }}</td>
                                        <td>{{ $seasonStat['STL'] ?? '-' }}</td>
                                        <td>{{ $seasonStat['BLK'] ?? '-' }}</td>
                                        <td>{{ $seasonStat['PTS'] ?? '-' }}</td>
                                        <td>{{ isset($seasonStat['PPG']) && $seasonStat['PPG'] !== '-' ? ($seasonStat['PPG'] < 1 && $seasonStat['PPG'] > 0 ? substr(number_format($seasonStat['PPG'], 1), 1) : number_format($seasonStat['PPG'], 1)) : '-' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                        {{-- Display career row only if there is career stats --}}
                        @if($player->hasCareerStats())
                            @php
                                $careerStat = $player->career_stat;
                            @endphp
                            <tr class="text-right font-bold">
                                <td class="text-left">Career</td>
                                <td class="text-left">{{ $careerStat['team'] ?? '-' }}</td>
                                <td>{{ $careerStat['G'] ?? '-' }}</td>
                                <td>{{ isset($careerStat['FG%']) && $careerStat['FG%'] !== '-' ? ($careerStat['FG%'] < 1 && $careerStat['FG%'] > 0 ? substr(number_format($careerStat['FG%'], 3), 1) : number_format($careerStat['FG%'], 3)) : '-' }}</td>
                                <td>{{ isset($careerStat['FT%']) && $careerStat['FT%'] !== '-' ? ($careerStat['FT%'] < 1 && $careerStat['FT%'] > 0 ? substr(number_format($careerStat['FT%'], 3), 1) : number_format($careerStat['FT%'], 3)) : '-' }}</td>
                                <td>{{ $careerStat['3PM'] ?? '-' }}</td>
                                <td>{{ isset($careerStat['RPG']) && $careerStat['RPG'] !== '-' ? ($careerStat['RPG'] < 1 && $careerStat['RPG'] > 0 ? substr(number_format($careerStat['RPG'], 1), 1) : number_format($careerStat['RPG'], 1)) : '-' }}</td>
                                <td>{{ isset($careerStat['APG']) && $careerStat['APG'] !== '-' ? ($careerStat['APG'] < 1 && $careerStat['APG'] > 0 ? substr(number_format($careerStat['APG'], 1), 1) : number_format($careerStat['APG'], 1)) : '-' }}</td>
                                <td>{{ $careerStat['STL'] ?? '-' }}</td>
                                <td>{{ $careerStat['BLK'] ?? '-' }}</td>
                                <td>{{ $careerStat['PTS'] ?? '-' }}</td>
                                <td>{{ isset($careerStat['PPG']) && $careerStat['PPG'] !== '-' ? ($careerStat['PPG'] < 1 && $careerStat['PPG'] > 0 ? substr(number_format($careerStat['PPG'], 1), 1) : number_format($careerStat['PPG'], 1)) : '-' }}</td>
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


        </div>

    </div>
</div>

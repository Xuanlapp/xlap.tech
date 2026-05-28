<?php

namespace App\Http\Controllers;

use App\Models\logo_nba_versions;
use App\Models\Nba_team;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class BasketballLogoApiController extends Controller
{
    public function getLogoFolderStructure($logo)
    {
        $basePath = 'Prepress5:PP_Masters:Logos:BASKETBALL:_NBA_MASTER_LOGOS:';
        $folderStructure = [];

        // 讀取配置文件
        $logoTypes = Config::get('basketball_logos.nba.logo_types', []);

        foreach ($logoTypes as $type => $config) {
            $isRC = isset($config['is_rc']) && $config['is_rc'];
            $isTeamColor = isset($config['is_team_color']) && $config['is_team_color'];

            if ($isRC) {
                // RC Logo 命名模式
                // 主色
                $fileName1 = str_replace(
                    ['{pickup_name}', '{suffix}'],
                    [$logo->pickup_name, $config['suffix']],
                    $config['file_pattern']['primary']
                );

                // 副色
                $fileName2 = str_replace(
                    ['{pickup_name}', '{suffix}'],
                    [$logo->pickup_name, $config['suffix']],
                    $config['file_pattern']['secondary']
                );

                $folderStructure[$type] = [
                    'primary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $fileName1,
                    'secondary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $fileName2
                ];
            } elseif ($isTeamColor) {
                // Team Color 命名模式
                $yearStr = (string)$logo->begin;
                $yearCode = '';
                if (strlen($yearStr) >= 3) {
                    $yearCode = substr($yearStr, 0, 1) . substr($yearStr, 2);
                } else {
                    $yearCode = $yearStr;
                }

                // 主色
                $fileName1 = str_replace(
                    ['{pickup_name}', '{year}', '{init_letters}', '{year_code}'],
                    [
                        $logo->pickup_name,
                        $logo->begin,
                        $logo->nba_team->init_letters,
                        $yearCode
                    ],
                    $config['file_pattern']['primary']
                );

                // 副色
                $fileName2 = str_replace(
                    ['{pickup_name}', '{year}', '{init_letters}', '{year_code}'],
                    [
                        $logo->pickup_name,
                        $logo->begin,
                        $logo->nba_team->init_letters,
                        $yearCode
                    ],
                    $config['file_pattern']['secondary']
                );

                $folderStructure[$type] = [
                    'primary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $fileName1,
                    'secondary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $fileName2
                ];
            } else {
                // 標準命名模式
                // 主色文件名
                $primaryFileName = $logo->logo_file_name_base() . $config['suffix'] . '1.ai';

                // 副色文件名（將1.ai替換為2.ai）
                $secondaryFileName = $logo->logo_file_name_base() . $config['suffix'] . '2.ai';

                $folderStructure[$type] = [
                    'primary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $primaryFileName,
                    'secondary' => $basePath . str_replace('/', ':', $config['path']) . ':' . $secondaryFileName
                ];
            }
        }

        return $folderStructure;
    }

    public function show($team, $year)
    {
        $team = Nba_team::where('team_name', $team)->first();
        $logo = logo_nba_versions::where('team_id', $team->id)
            ->where('begin', '<=', $year)
            ->where('end', '>=', $year)
            ->first();
        $logoFolderStructure = $this->getLogoFolderStructure($logo);

        if (!$logoFolderStructure) {
            return response()->json(['error' => 'Logo not found'], 404);
        }

        return response()->json($logoFolderStructure);
    }
}

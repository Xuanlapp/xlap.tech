<?php

namespace App\Http\Livewire\Modals\Logos\Nba;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class CheckLogoVersion extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $version, $team, $year, $logoData, $lap;
    public $isLoading = true;
    public $logoCheckResults = [];
    public $logoTypes = [];

    public function mount()
    {
        // 檢查 version 是否為數組並且包含必要的鍵
        if (!is_array($this->version) || !isset($this->version['nba_team']) || !isset($this->version['begin'])) {
            $this->showAlertMessage('error', 'Invalid version parameter');
            $this->isLoading = false;
            return;
        }

        $this->team = $this->version['nba_team']['team_name'];
        $this->year = $this->version['begin'];

        // Load logo types from config
        $this->logoTypes = Config::get('basketball_logos.nba.logo_types', []);

        $this->checkLogoFiles();
    }

    /**
     * 在指定目錄下尋找最佳路徑匹配 (大小寫不敏感)
     * @param string $basePath 基礎路徑
     * @param array $pathSegments 路徑段數組
     * @return string|null 找到的最佳路徑或null
     */
    private function findBestPathMatch($basePath, $pathSegments)
    {
        if (empty($pathSegments)) {
            return $basePath;
        }

        $currentPath = $basePath;
        $resultPath = null;

        foreach ($pathSegments as $segment) {
            // 嘗試直接匹配
            $testPath = $currentPath . DIRECTORY_SEPARATOR . $segment;
            if (File::exists($testPath)) {
                $currentPath = $testPath;
                $resultPath = $currentPath;
                continue;
            }

            // 如果直接匹配失敗，嘗試不區分大小寫的搜索
            $found = false;
            $directories = File::directories($currentPath);
            foreach ($directories as $dir) {
                $dirName = basename($dir);
                if (strcasecmp($dirName, $segment) === 0) {
                    $currentPath = $dir;
                    $resultPath = $currentPath;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // 如果還是找不到，記錄並停止搜索
                $resultPath = null;
                break;
            }
        }

        return $resultPath;
    }

    /**
     * Check if logo files exist for each logo type
     */
    public function checkLogoFiles()
    {
        if (empty($this->version) || !isset($this->version['nba_team'])) {
            return;
        }

        $team = $this->version['nba_team'];
        $year = $this->version['begin'];

        // Get the year code (year without second digit)
        $yearStr = (string)$year;
        $yearCode = '';
        if (strlen($yearStr) >= 3) {
            $yearCode = substr($yearStr, 0, 1) . substr($yearStr, 2);
        } else {
            $yearCode = $yearStr;
        }

        foreach ($this->logoTypes as $type => $config) {
            // 保存原始配置路徑
            $configPath = $config['path'];

            // 檢查是否為 RC Logo 或 Team Color Logo
            $isRC = isset($config['is_rc']) && $config['is_rc'];
            $isTeamColor = isset($config['is_team_color']) && $config['is_team_color'];

            // 創建要檢查的文件名列表 (主色和副色)
            $filesToCheck = [];

            if ($isRC) {
                // RC Logo 命名模式: {pickup_name}_ATL_RC_{suffix}1.ai
                $filesToCheck['primary'] = str_replace(
                    ['{pickup_name}', '{suffix}'],
                    [
                        $team['pickup_name'] ?? $team['stat_name'],
                        $config['suffix']
                    ],
                    $config['file_pattern']['primary']
                );

                $filesToCheck['secondary'] = str_replace(
                    ['{pickup_name}', '{suffix}'],
                    [
                        $team['pickup_name'] ?? $team['stat_name'],
                        $config['suffix']
                    ],
                    $config['file_pattern']['secondary']
                );
            } elseif ($isTeamColor) {
                // Team Color 命名模式: {pickup_name}_ATL_{year}_{init_letters}{year_code}A1_TC1.ai
                $filesToCheck['primary'] = str_replace(
                    ['{pickup_name}', '{year}', '{init_letters}', '{year_code}'],
                    [
                        $team['pickup_name'] ?? $team['stat_name'],
                        $year,
                        $team['init_letters'],
                        $yearCode
                    ],
                    $config['file_pattern']['primary']
                );

                $filesToCheck['secondary'] = str_replace(
                    ['{pickup_name}', '{year}', '{init_letters}', '{year_code}'],
                    [
                        $team['pickup_name'] ?? $team['stat_name'],
                        $year,
                        $team['init_letters'],
                        $yearCode
                    ],
                    $config['file_pattern']['secondary']
                );
            } else {
                // 標準命名模式: {pickup_name}_{year}_{init_letters}{year_code}{suffix}1.ai
                $basePattern = Config::get('basketball_logos.nba.file_pattern');

                // 只檢查主色，標準Logo類型沒有副色
                $filesToCheck['primary'] = str_replace(
                    ['{pickup_name}', '{year}', '{init_letters}', '{year_code}', '{suffix}'],
                    [
                        $team['pickup_name'] ?? $team['stat_name'],
                        $year,
                        $team['init_letters'],
                        $yearCode,
                        $config['suffix']
                    ],
                    $basePattern
                );

                // 不再為標準Logo類型檢查副色
            }

            $pathSegments = explode('/', $configPath);
            $this->logoCheckResults[$type] = [];

            // 首先獲取目錄路徑
            $basePath = public_path('Prepress5');
            $directorySegments = $pathSegments;
            if (!empty($directorySegments) && $directorySegments[0] == 'Prepress5') {
                array_shift($directorySegments); // 移除 'Prepress5'
            }
            $bestMatchPath = $this->findBestPathMatch($basePath, $directorySegments);

            // 檢查每個文件 (主色和副色)
            foreach ($filesToCheck as $colorType => $fileName) {
                $exists = false;
                $osCheckPath = ''; // 用於檢查的系統路徑
                $displayPath = ''; // 用於顯示的網頁路徑

                if ($bestMatchPath) {
                    // 使用系統適當的目錄分隔符構建完整文件路徑
                    $osCheckPath = $bestMatchPath . DIRECTORY_SEPARATOR . $fileName;
                    $exists = File::exists($osCheckPath);

                    // 獲取相對於 public 目錄的路徑用於顯示
                    $relativePath = substr($bestMatchPath, strlen(public_path()) + 1);
                    // 確保 Web 顯示路徑使用正斜線
                    $displayPath = str_replace('\\', '/', $relativePath) . '/' . $fileName;
                } else {
                    // 使用原始配置路徑（用於顯示）
                    $displayPath = $configPath . '/' . $fileName;
                    // 使用系統適當的目錄分隔符檢查文件存在
                    $osPath = str_replace('/', DIRECTORY_SEPARATOR, $configPath);
                    $osCheckPath = public_path($osPath . DIRECTORY_SEPARATOR . $fileName);
                    $exists = File::exists($osCheckPath);
                }

                // 存儲結果
                $this->logoCheckResults[$type][$colorType] = [
                    'filePath' => $displayPath,
                    'os_specific_path' => $osCheckPath,
                    'web_path' => $displayPath,
                    'exists' => $exists,
                    'color_type' => $colorType
                ];
            }
        }

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.modals.logos.nba.check-logo-version', [
            'logoCheckResults' => $this->logoCheckResults,
            'isLoading' => $this->isLoading,
            'versionData' => $this->version
        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}

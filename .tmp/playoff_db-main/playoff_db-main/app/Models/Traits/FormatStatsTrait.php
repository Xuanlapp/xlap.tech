<?php

namespace App\Models\Traits;

trait FormatStatsTrait
{
    /**
     * 格式化統計數據，只對百分比數據去除小數點前的零
     *
     * @param mixed $value 要格式化的值
     * @param string $type 統計類型 (percentage|average)
     * @return string 格式化後的值
     */
    protected function formatStatValue($value, $type = 'percentage')
    {
        // 如果值不是數字或是 "-"，直接返回
        if (!is_numeric($value) || $value === '-') {
            return $value;
        }

        // 根據統計類型進行格式化
        if ($type === 'percentage') {
            // 對於射擊百分比：顯示 .xxx 格式（如果小於1）或 x.xxx 格式
            $formatted = number_format((float)$value, 3, '.', '');

            // 如果值小於1且大於0，去掉前面的0 (只針對百分比)
            if ($formatted < 1 && $formatted > 0) {
                $formatted = substr($formatted, 1);
            }

            return $formatted;
        } elseif ($type === 'average') {
            // 對於每場平均數據：顯示 0.x 或 x.x 格式，保留前導零
            return number_format((float)$value, 1, '.', '');
        }

        return $value;
    }

    /**
     * 格式化百分比統計數據
     *
     * @param mixed $value 要格式化的值
     * @return string 格式化後的值
     */
    protected function formatPercentage($value)
    {
        return $this->formatStatValue($value, 'percentage');
    }

    /**
     * 格式化平均值統計數據
     *
     * @param mixed $value 要格式化的值
     * @return string 格式化後的值
     */
    protected function formatAverage($value)
    {
        return $this->formatStatValue($value, 'average');
    }
}

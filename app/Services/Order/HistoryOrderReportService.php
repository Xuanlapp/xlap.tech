<?php

namespace App\Services\Order;

use App\Models\HistoryOrderReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use RuntimeException;

class HistoryOrderReportService
{
    /**
     * Persist a completed order submission and refuse the same order for the same user.
     *
     * @param array<int, string> $imageLinks
     * @param array<string, mixed> $reportData
     */
    public function record(User $user, string $orderId, array $imageLinks, array $reportData = [], ?Carbon $orderedAt = null): HistoryOrderReport
    {
        $orderId = trim($orderId);

        if ($orderId === '') {
            throw new RuntimeException('Order ID khong duoc de trong.');
        }

        if (HistoryOrderReport::query()->where('user_id', $user->id)->where('order_id', $orderId)->exists()) {
            throw new RuntimeException("Don {$orderId} da co trong history, khong the len don lan nua.");
        }

        try {
            return HistoryOrderReport::query()->create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'images_link' => array_values(array_filter($imageLinks, fn (mixed $url): bool => is_string($url) && trim($url) !== '')),
                'report_data' => $reportData ?: null,
                'ordered_at' => $orderedAt,
            ]);
        } catch (QueryException $exception) {
            if ((int) $exception->errorInfo[1] === 1062) {
                throw new RuntimeException("Don {$orderId} da co trong history, khong the len don lan nua.", 0, $exception);
            }

            throw $exception;
        }
    }
}

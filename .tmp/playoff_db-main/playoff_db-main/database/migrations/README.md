# 聯絡人管理系統遷移文件

本目錄包含聯絡人管理系統的數據庫遷移文件。這些文件定義了系統所需的表結構。

## 表結構

### 1. contact_locations (位置表)

存儲公司的不同位置信息。

| 欄位名        | 類型   | 說明     |
| ------------- | ------ | -------- |
| id            | bigint | 主鍵     |
| location_name | string | 位置名稱 |
| address       | string | 地址     |

### 2. contact_departments (部門表)

存儲公司的不同部門信息，與位置表關聯。

| 欄位名          | 類型   | 說明                              |
| --------------- | ------ | --------------------------------- |
| id              | bigint | 主鍵                              |
| department_name | string | 部門名稱                          |
| location_id     | bigint | 外鍵，關聯到 contact_locations 表 |

### 3. contact_employees (員工表)

存儲員工信息，與部門表關聯。

| 欄位名        | 類型       | 說明                                |
| ------------- | ---------- | ----------------------------------- |
| id            | bigint     | 主鍵                                |
| name          | string     | 員工姓名                            |
| email         | string     | 電子郵件                            |
| phone         | string(10) | 電話號碼                            |
| position      | string     | 職位                                |
| department_id | bigint     | 外鍵，關聯到 contact_departments 表 |
| profile_image | string     | 個人頭像，可為空                    |

## 關係

1. 一個位置可以有多個部門 (一對多)
2. 一個部門可以有多個員工 (一對多)
3. 一個員工屬於一個部門 (多對一)

## 運行遷移

使用以下命令運行遷移：

```bash
php artisan migrate
```

如需回滾遷移：

```bash
php artisan migrate:rollback
```

## 注意事項

-   所有表都沒有時間戳 (created_at, updated_at)
-   刪除位置時，相關的部門也會被刪除 (級聯刪除)
-   刪除部門時，相關的員工也會被刪除 (級聯刪除)

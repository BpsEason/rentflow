#!/bin/bash

set -e

# 設定 Laravel 必要目錄的權限
# 只設定需要寫入的目錄，不影響整個專案
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# 確保目錄有正確的寫入權限
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# 安全地建立 storage 符號連結 - 只在必要時執行
PUBLIC_STORAGE="/var/www/html/public/storage"
STORAGE_TARGET="/var/www/html/storage/app/public"

# 檢查目標目錄是否存在
if [ ! -d "$STORAGE_TARGET" ]; then
    echo "警告: 目標儲存目錄 $STORAGE_TARGET 不存在，正在建立..."
    mkdir -p "$STORAGE_TARGET"
    chown www-data:www-data "$STORAGE_TARGET"
fi

# 檢查 public/storage 的狀態
if [ -L "$PUBLIC_STORAGE" ]; then
    # 已經是符號連結，檢查目標是否正確
    CURRENT_TARGET=$(readlink -f "$PUBLIC_STORAGE")
    EXPECTED_TARGET=$(readlink -f "$STORAGE_TARGET")
    
    if [ "$CURRENT_TARGET" = "$EXPECTED_TARGET" ]; then
        echo "符號連結已存在且正確: $PUBLIC_STORAGE -> $STORAGE_TARGET"
    else
        echo "錯誤: 現有符號連結指向錯誤的目標 $CURRENT_TARGET，預期 $EXPECTED_TARGET"
        echo "請手動檢查並修正 public/storage 符號連結"
    fi
elif [ -d "$PUBLIC_STORAGE" ]; then
    # 已經是實體目錄，保留既有資料
    echo "public/storage 已是實體目錄，保留既有圖片資料"
    # 確保權限正確
    chown -R www-data:www-data "$PUBLIC_STORAGE"
    chmod -R 755 "$PUBLIC_STORAGE"
elif [ ! -e "$PUBLIC_STORAGE" ]; then
    # 不存在，建立符號連結
    echo "正在建立 storage 符號連結: $PUBLIC_STORAGE -> $STORAGE_TARGET"
    php artisan storage:link
    
    # 確保連結權限正確
    chown www-data:www-data "$PUBLIC_STORAGE"
else
    # 其他類型的檔案
    echo "警告: public/storage 存在但不是目錄或符號連結，類型: $(file -b "$PUBLIC_STORAGE")"
fi

# 執行原始的 php-fpm 啟動命令
exec "$@"
#!/bin/bash

set -e

# 設定 Laravel 必要目錄的權限
# 只設定需要寫入的目錄，不影響整個專案
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# 確保目錄有正確的寫入權限
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# 執行原始的 php-fpm 啟動命令
exec "$@"
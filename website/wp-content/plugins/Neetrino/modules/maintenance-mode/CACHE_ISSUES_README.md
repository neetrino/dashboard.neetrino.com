# Решение проблем с кешированием в режиме обслуживания

## Проблема
При включении режима обслуживания сайт возвращает код 503, который сильно кешируется браузерами и серверами. После отключения режима сайт может продолжать показывать страницу обслуживания.

## Автоматические решения (уже реализованы)

### 1. Улучшенные заголовки против кеширования
- `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
- `Pragma: no-cache`
- `Expires: Wed, 11 Jan 1984 05:00:00 GMT`
- Уникальные ETag и Last-Modified на каждый запрос

### 2. Автоматическая проверка статуса
- JavaScript проверяет статус каждые 30 секунд
- Автоматическое обновление страницы при отключении режима
- Fallback механизм для надежности

### 3. Сокращенное время Retry-After
- Изменено с 3600 секунд (1 час) на 60 секунд
- Браузеры будут повторять попытки чаще

### 4. Автоматическая очистка кеша
- Очистка WordPress кеша при изменении режима
- Очистка OPCache если доступен
- Создание timestamp файлов для cache busting

## Дополнительные решения для сервера

### Для Apache (.htaccess)
Плагин автоматически добавляет правила в .htaccess, но вы можете проверить их вручную:

```apache
# Prevent caching of maintenance mode pages
<IfModule mod_headers.c>
    <FilesMatch "\.(php)$">
        Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
        Header set Pragma "no-cache"
        Header set Expires "Wed, 11 Jan 1984 05:00:00 GMT"
    </FilesMatch>
</IfModule>

# Force revalidation
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/html "access plus 0 seconds"
</IfModule>
```

### Для Nginx
Добавьте в конфигурацию сервера:

```nginx
location / {
    # Disable caching for maintenance mode
    add_header Cache-Control "no-store, no-cache, must-revalidate, max-age=0";
    add_header Pragma "no-cache";
    add_header Expires "Wed, 11 Jan 1984 05:00:00 GMT";
    
    try_files $uri $uri/ /index.php?$args;
}
```

### Для Cloudflare
1. Зайдите в панель Cloudflare
2. Перейдите в Caching → Purge Cache
3. Выберите "Purge Everything" при отключении режима обслуживания

### Для других CDN
- KeyCDN: Purge → Purge All
- MaxCDN: Cache → Purge All Files
- AWS CloudFront: Invalidations → Create Invalidation

## Ручные решения

### Для пользователей
Если сайт все еще показывает страницу обслуживания:

1. **Принудительное обновление**: Ctrl+F5 (Windows) или Cmd+Shift+R (Mac)
2. **Очистка кеша браузера**: Ctrl+Shift+Del
3. **Режим инкогнито**: Откройте сайт в приватном режиме
4. **Другой браузер**: Попробуйте другой браузер

### Для администраторов
1. **Очистка server-side кеша**:
   ```bash
   # Nginx
   sudo systemctl reload nginx
   
   # Apache
   sudo systemctl reload apache2
   
   # PHP-FPM
   sudo systemctl reload php-fpm
   ```

2. **Проверка логов**:
   ```bash
   tail -f /var/log/nginx/error.log
   tail -f /var/log/apache2/error.log
   ```

## Диагностика

### Проверка статуса через curl
```bash
curl -I "http://yoursite.com" | grep "HTTP\|Cache-Control\|Expires"
```

### Проверка логов WordPress
Плагин записывает изменения режима в error_log:
```
Neetrino Maintenance Mode changed from {"mode":"maintenance"} to {"mode":"open"}
```

## Настройки для предотвращения проблем

### 1. Настройка CDN
Исключите страницы обслуживания из кеширования:
- Добавьте URL patterns в исключения CDN
- Установите короткое время кеширования для статических ресурсов

### 2. Настройка хостинга
Обратитесь к хостинг-провайдеру для:
- Отключения server-side кеширования для maintenance страниц
- Настройки правильных заголовков кеширования
- Возможности быстрой очистки кеша

### 3. Плагины кеширования WordPress
Добавьте исключения в плагины кеширования:
- WP Rocket: исключите maintenance URLs
- W3 Total Cache: отключите для maintenance страниц
- WP Super Cache: добавьте в rejected URIs

## Мониторинг
Плагин теперь создает файл timestamp при изменении режима:
`/wp-content/maintenance-mode-timestamp.txt`

Используйте этот файл для мониторинга изменений режима обслуживания.

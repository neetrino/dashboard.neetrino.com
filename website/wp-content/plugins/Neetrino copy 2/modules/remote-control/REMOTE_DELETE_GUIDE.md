# Руководство по команде удаления плагина

## Обзор

В модуль Remote Control добавлена новая команда `delete_plugin` для полного удаления плагина Neetrino через HTTP API.

## Безопасность

Команда имеет дополнительные меры безопасности:
- Требует валидный API ключ (как и все команды)
- Требует обязательный параметр подтверждения `confirm=YES_DELETE_PLUGIN`
- Проверяет лимиты запросов
- Логирует все действия

## Использование

### URL для команды:
```
https://your-site.com/?remote_control=delete_plugin&confirm=YES_DELETE_PLUGIN&key=YOUR_API_KEY
```

### Параметры:
- `remote_control=delete_plugin` - команда удаления
- `confirm=YES_DELETE_PLUGIN` - обязательное подтверждение (точно этот текст)
- `key=YOUR_API_KEY` - ваш API ключ из админки

## Что делает команда

1. **Деактивирует плагин** (если активен)
2. **Удаляет все файлы** плагина из `/wp-content/plugins/Neetrino/`
3. **Очищает базу данных** от всех опций плагина:
   - Опции с префиксами: `neetrino_`, `bitrix24_`, `remote_control_`
   - Конкретные опции: `neetrino_active_modules`, `neetrino_maintenance_mode`, `neetrino_version`
4. **Очищает кеш** WordPress
5. **Логирует операцию** для аудита

## Ответ API

### Успешное удаление:
```json
{
  "success": true,
  "data": {
    "message": "Plugin deleted successfully",
    "deleted_files": true,
    "cleaned_database": true,
    "timestamp": "2025-07-25 10:30:15"
  }
}
```

### Ошибки:
```json
{
  "success": false,
  "data": "Error message here"
}
```

## Возможные ошибки

- `Invalid API key` (403) - неверный ключ
- `Rate limit exceeded` (429) - превышен лимит запросов
- `Delete plugin command requires confirmation parameter: confirm=YES_DELETE_PLUGIN` (400) - отсутствует подтверждение
- `Plugin directory not found` (404) - плагин уже удален
- `Plugin deletion failed: [error details]` (500) - ошибка при удалении

## Важные замечания

⚠️ **ВНИМАНИЕ!** Эта операция необратима!

- Сделайте резервную копию перед использованием
- Убедитесь, что у вас есть альтернативный способ доступа к сайту
- Команда удалит ВСЕ файлы и настройки плагина
- После удаления восстановить данные будет невозможно без резервной копии

## Просмотр в админке

Новая команда отображается в админке WordPress:
**Neetrino → Remote Control → [Показать API команды]**

Команда выделена красным цветом и имеет предупреждения о том, что это деструктивная операция.

## Логирование

Все действия команды логируются в WordPress error log:
- Получение команды
- Проверка подтверждения
- Деактивация плагина
- Очистка базы данных
- Удаление файлов
- Результат операции

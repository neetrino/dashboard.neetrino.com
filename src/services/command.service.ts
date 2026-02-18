/**
 * Сервис для отправки команд на сайты (PUSH архитектура)
 */

import { config } from '@/lib/config';
import { createLogger } from '@/lib/logger';
import type { SiteCommand, CommandResponse, SiteWithVersion } from '@/types';

const logger = createLogger('command.service');

/**
 * Отправка команды на сайт через REST API
 */
export async function sendCommand(
  site: SiteWithVersion,
  command: SiteCommand,
  data: Record<string, unknown> = {}
): Promise<CommandResponse> {
  if (!site.apiKey) {
    throw new Error('API ключ не найден для сайта');
  }
  
  const url = `${site.siteUrl}/wp-json/neetrino/v1/command`;
  
  const controller = new AbortController();
  const timeoutId = setTimeout(
    () => controller.abort(),
    config.COMMAND_TIMEOUT
  );
  
  try {
    logger.debug({ siteId: site.id, command, url }, 'Sending command');
    
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        command,
        data,
        api_key: site.apiKey,
      }),
      signal: controller.signal,
    });
    
    clearTimeout(timeoutId);
    
    if (!response.ok) {
      const errorText = await response.text().catch(() => '');
      
      if (response.status === 401) {
        throw new Error('Ошибка авторизации - проверьте API ключ');
      }
      
      if (response.status === 426) {
        throw new Error('Требуется обновить плагин');
      }
      
      throw new Error(`HTTP ${response.status}: ${errorText || response.statusText}`);
    }
    
    const result = await response.json();
    
    logger.debug({ siteId: site.id, command, success: result.success }, 'Command completed');
    
    return {
      success: result.success ?? true,
      command,
      message: result.message || 'Команда выполнена',
      data: result.data,
      timestamp: result.timestamp || Math.floor(Date.now() / 1000),
    };
    
  } catch (error) {
    clearTimeout(timeoutId);
    
    if (error instanceof Error) {
      if (error.name === 'AbortError') {
        logger.warn({ siteId: site.id, command }, 'Command timeout');
        throw new Error('Таймаут выполнения команды');
      }
      
      logger.error({ siteId: site.id, command, error: error.message }, 'Command failed');
      throw error;
    }
    
    throw new Error('Неизвестная ошибка');
  }
}

/**
 * Проверка статуса сайта
 */
export async function checkSiteStatus(site: SiteWithVersion): Promise<{
  online: boolean;
  pluginVersion?: string;
  maintenanceMode?: { mode: string };
  data?: Record<string, unknown>;
}> {
  try {
    const result = await sendCommand(site, 'get_status');
    
    return {
      online: true,
      pluginVersion: result.data?.plugin_version as string | undefined,
      maintenanceMode: result.data?.maintenance_mode as { mode: string } | undefined,
      data: result.data,
    };
  } catch {
    return { online: false };
  }
}

/**
 * Получение информации о сайте
 */
export async function getSiteInfo(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'get_info');
}

/**
 * Обновление плагина
 */
export async function updatePlugin(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'update_plugins');
}

/**
 * Деактивация плагина
 */
export async function deactivatePlugin(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'deactivate_plugin');
}

/**
 * Удаление плагина с сайта
 */
export async function deletePluginFromSite(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'delete_plugin');
}

/**
 * Очистка кэша
 */
export async function clearCache(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'clear_cache');
}

/**
 * Создание бэкапа
 */
export async function createBackup(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'backup_create');
}

/**
 * Оптимизация БД
 */
export async function optimizeDatabase(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'optimize_db');
}

/**
 * Обновление WordPress
 */
export async function updateWordPress(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'update_core');
}

/**
 * Сканирование безопасности
 */
export async function securityScan(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'security_scan');
}

/**
 * Тест производительности
 */
export async function performanceTest(site: SiteWithVersion): Promise<CommandResponse> {
  return sendCommand(site, 'performance_test');
}

/**
 * Установка режима обслуживания
 */
export async function setMaintenanceMode(
  site: SiteWithVersion,
  mode: 'open' | 'maintenance' | 'closed'
): Promise<CommandResponse> {
  return sendCommand(site, 'maintenance_mode', { mode });
}

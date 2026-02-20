/**
 * Сервис системных настроек (ключ-значение)
 */

import prisma from '@/lib/prisma';
import { createLogger } from '@/lib/logger';

const logger = createLogger('settings.service');

const PLUGIN_PACKAGE_PATH_KEY = 'plugin_package_path';

/**
 * Получить значение настройки по ключу
 */
export async function getSystemSetting(key: string): Promise<string | null> {
  const row = await prisma.systemSetting.findUnique({
    where: { settingKey: key },
  });
  return row?.settingValue ?? null;
}

/**
 * Установить значение настройки
 */
export async function setSystemSetting(
  key: string,
  value: string,
  type: 'string' | 'integer' | 'boolean' | 'json' = 'string'
): Promise<void> {
  await prisma.systemSetting.upsert({
    where: { settingKey: key },
    create: { settingKey: key, settingValue: value, settingType: type },
    update: { settingValue: value, settingType: type },
  });
  logger.debug({ key }, 'System setting updated');
}

/**
 * Путь к загруженному ZIP плагина (относительно process.cwd) или null
 */
export async function getPluginPackagePath(): Promise<string | null> {
  return getSystemSetting(PLUGIN_PACKAGE_PATH_KEY);
}

/**
 * Сохранить путь к загруженному ZIP плагина
 */
export async function setPluginPackagePath(relativePath: string): Promise<void> {
  await setSystemSetting(PLUGIN_PACKAGE_PATH_KEY, relativePath);
}

/**
 * Удалить настройку (после удаления файла)
 */
export async function removePluginPackagePath(): Promise<void> {
  await prisma.systemSetting.deleteMany({
    where: { settingKey: PLUGIN_PACKAGE_PATH_KEY },
  });
}

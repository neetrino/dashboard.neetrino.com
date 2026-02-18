/**
 * Сервис для работы с корзиной
 */

import prisma from '@/lib/prisma';
import { normalizeUrlToDomain } from '@/lib/utils';
import { createLogger } from '@/lib/logger';
import type { TrashItem } from '@/types';

const logger = createLogger('trash.service');

/**
 * Получение списка элементов в корзине
 */
export async function getTrashItems(): Promise<TrashItem[]> {
  const items = await prisma.trash.findMany({
    orderBy: { deletedAt: 'desc' },
  });
  
  return items.map((item) => ({
    id: item.id,
    siteUrl: item.siteUrl,
    siteName: item.siteName,
    activeModules: item.activeModules,
    originalSiteId: item.originalSiteId,
    deletedAt: item.deletedAt,
    deletedReason: item.deletedReason,
    deletedByAdminId: item.deletedByAdminId,
    restoreData: item.restoreData as Record<string, unknown> | null,
  }));
}

/**
 * Получение элемента корзины по ID
 */
export async function getTrashItemById(id: number): Promise<TrashItem | null> {
  const item = await prisma.trash.findUnique({
    where: { id },
  });
  
  if (!item) return null;
  
  return {
    id: item.id,
    siteUrl: item.siteUrl,
    siteName: item.siteName,
    activeModules: item.activeModules,
    originalSiteId: item.originalSiteId,
    deletedAt: item.deletedAt,
    deletedReason: item.deletedReason,
    deletedByAdminId: item.deletedByAdminId,
    restoreData: item.restoreData as Record<string, unknown> | null,
  };
}

/**
 * Восстановление сайта из корзины
 */
export async function restoreFromTrash(trashId: number): Promise<{
  success: boolean;
  siteId?: number;
  error?: string;
}> {
  const trashItem = await prisma.trash.findUnique({
    where: { id: trashId },
  });
  
  if (!trashItem) {
    return { success: false, error: 'Элемент не найден в корзине' };
  }
  
  // Проверяем, нет ли уже сайта с таким доменом
  const domain = normalizeUrlToDomain(trashItem.siteUrl);
  const existingSite = await prisma.site.findFirst({
    where: {
      OR: [
        { siteUrl: { contains: `://${domain}` } },
        { siteUrl: { contains: `://www.${domain}` } },
      ],
    },
  });
  
  if (existingSite) {
    return { success: false, error: 'Сайт с таким доменом уже существует' };
  }
  
  // Извлекаем дополнительные данные
  const restoreData = trashItem.restoreData as Record<string, unknown> || {};
  
  // Восстанавливаем сайт
  const site = await prisma.site.create({
    data: {
      siteUrl: trashItem.siteUrl,
      siteName: trashItem.siteName,
      adminEmail: (restoreData.adminEmail as string) || '',
      dashboardIp: (restoreData.dashboardIp as string) || null,
      activeModules: trashItem.activeModules,
      status: 'offline',
    },
  });
  
  // Удаляем из корзины
  await prisma.trash.delete({
    where: { id: trashId },
  });
  
  logger.info({ trashId, siteId: site.id }, 'Site restored from trash');
  
  return { success: true, siteId: site.id };
}

/**
 * Массовое восстановление всех сайтов из корзины
 */
export async function restoreAllFromTrash(): Promise<{
  restored: number;
  skipped: number;
  errors: string[];
}> {
  const trashItems = await prisma.trash.findMany({
    orderBy: { deletedAt: 'desc' },
  });
  
  let restored = 0;
  let skipped = 0;
  const errors: string[] = [];
  
  for (const item of trashItems) {
    const result = await restoreFromTrash(item.id);
    
    if (result.success) {
      restored++;
    } else if (result.error?.includes('уже существует')) {
      skipped++;
    } else {
      errors.push(`${item.siteName}: ${result.error}`);
    }
  }
  
  logger.info({ restored, skipped, errorsCount: errors.length }, 'Bulk restore completed');
  
  return { restored, skipped, errors };
}

/**
 * Полное удаление элемента из корзины
 */
export async function permanentlyDelete(trashId: number): Promise<boolean> {
  try {
    await prisma.trash.delete({
      where: { id: trashId },
    });
    
    logger.info({ trashId }, 'Trash item permanently deleted');
    return true;
  } catch {
    return false;
  }
}

/**
 * Очистка корзины (удаление всех элементов)
 */
export async function emptyTrash(): Promise<number> {
  const result = await prisma.trash.deleteMany();
  
  logger.info({ deletedCount: result.count }, 'Trash emptied');
  
  return result.count;
}

/**
 * Получение количества элементов в корзине
 */
export async function getTrashCount(): Promise<number> {
  return prisma.trash.count();
}

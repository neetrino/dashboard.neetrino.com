/**
 * Сервис для работы с сайтами
 * Бизнес-логика перенесена из PHP
 */

import prisma from '@/lib/prisma';
import { generateApiKey, hashApiKey } from '@/lib/security';
import { normalizeUrlToDomain } from '@/lib/utils';
import { createLogger } from '@/lib/logger';
import type { 
  Site, 
  SiteWithVersion, 
  SiteFilters, 
  PaginatedResponse, 
  SiteStatus 
} from '@/types';

const logger = createLogger('sites.service');

/**
 * Получение списка сайтов с пагинацией и фильтрацией
 */
export async function getSites(filters: SiteFilters): Promise<PaginatedResponse<SiteWithVersion>> {
  const { search, status, page = 1, perPage = 20 } = filters;
  
  // Формируем условия WHERE
  const where: Record<string, unknown> = {};
  
  if (search) {
    where.OR = [
      { siteName: { contains: search } },
      { siteUrl: { contains: search } },
    ];
  }
  
  if (status && status !== 'all') {
    where.status = status;
  }
  
  // Получаем общее количество
  const totalItems = await prisma.site.count({ where });
  
  // Получаем сайты с версиями
  const sites = await prisma.site.findMany({
    where,
    include: {
      siteVersion: true,
    },
    orderBy: { siteName: 'asc' },
    skip: (page - 1) * perPage,
    take: perPage,
  });
  
  // Преобразуем результат
  const items: SiteWithVersion[] = sites.map((site) => ({
    id: site.id,
    siteUrl: site.siteUrl,
    siteName: site.siteName,
    adminEmail: site.adminEmail,
    apiKey: site.apiKey,
    apiKeyHash: site.apiKeyHash,
    dashboardIp: site.dashboardIp,
    dateAdded: site.dateAdded,
    activeModules: site.activeModules,
    status: site.status as SiteStatus,
    createdAt: site.createdAt,
    lastSeen: site.lastSeen,
    updatedAt: site.updatedAt,
    siteVersion: site.siteVersion ? {
      pluginVersion: site.siteVersion.pluginVersion,
      lastSeenAt: site.siteVersion.lastSeenAt,
      source: site.siteVersion.source as 'push' | 'pull',
      signatureOk: site.siteVersion.signatureOk,
    } : null,
  }));
  
  const totalPages = Math.ceil(totalItems / perPage);
  
  logger.debug({ totalItems, page, perPage }, 'Sites fetched');
  
  return {
    items,
    pagination: {
      currentPage: page,
      perPage,
      totalItems,
      totalPages,
      hasNext: page < totalPages,
      hasPrev: page > 1,
    },
  };
}

/**
 * Получение сайта по ID
 */
export async function getSiteById(id: number): Promise<SiteWithVersion | null> {
  const site = await prisma.site.findUnique({
    where: { id },
    include: { siteVersion: true },
  });
  
  if (!site) return null;
  
  return {
    id: site.id,
    siteUrl: site.siteUrl,
    siteName: site.siteName,
    adminEmail: site.adminEmail,
    apiKey: site.apiKey,
    apiKeyHash: site.apiKeyHash,
    dashboardIp: site.dashboardIp,
    dateAdded: site.dateAdded,
    activeModules: site.activeModules,
    status: site.status as SiteStatus,
    createdAt: site.createdAt,
    lastSeen: site.lastSeen,
    updatedAt: site.updatedAt,
    siteVersion: site.siteVersion ? {
      pluginVersion: site.siteVersion.pluginVersion,
      lastSeenAt: site.siteVersion.lastSeenAt,
      source: site.siteVersion.source as 'push' | 'pull',
      signatureOk: site.siteVersion.signatureOk,
    } : null,
  };
}

/**
 * Поиск сайта по домену
 */
export async function findSiteByDomain(url: string): Promise<Site | null> {
  const domain = normalizeUrlToDomain(url);
  
  const site = await prisma.site.findFirst({
    where: {
      OR: [
        { siteUrl: { contains: `://${domain}` } },
        { siteUrl: { contains: `://www.${domain}` } },
      ],
    },
  });
  
  return site as Site | null;
}

/**
 * Создание нового сайта
 */
export async function createSite(data: {
  siteUrl: string;
  siteName: string;
  adminEmail?: string;
}): Promise<Site> {
  const domain = normalizeUrlToDomain(data.siteUrl);
  
  // Проверяем, не существует ли сайт с таким доменом
  const existing = await findSiteByDomain(data.siteUrl);
  if (existing) {
    throw new Error(`Сайт с доменом ${domain} уже существует`);
  }
  
  const site = await prisma.site.create({
    data: {
      siteUrl: data.siteUrl.replace(/\/$/, ''),
      siteName: data.siteName,
      adminEmail: data.adminEmail || '',
      status: 'offline',
    },
  });
  
  logger.info({ siteId: site.id, siteUrl: site.siteUrl }, 'Site created');
  
  return site as Site;
}

/**
 * Регистрация сайта с генерацией API ключа
 * (вызывается при первой регистрации плагина)
 */
export async function registerSite(data: {
  siteUrl: string;
  siteName?: string;
  adminEmail?: string;
  pluginVersion?: string;
  dashboardIp: string;
  dashboardDomain: string;
}): Promise<{
  site: Site;
  apiKey: string;
  isNew: boolean;
}> {
  const domain = normalizeUrlToDomain(data.siteUrl);
  
  // Ищем существующий сайт
  const existing = await findSiteByDomain(data.siteUrl);
  
  // Генерируем API ключ
  const apiKey = generateApiKey();
  const apiKeyHash = hashApiKey(apiKey);
  
  let site: Site;
  let isNew = false;
  
  if (existing) {
    // Обновляем существующий сайт
    site = await prisma.site.update({
      where: { id: existing.id },
      data: {
        siteUrl: data.siteUrl.replace(/\/$/, ''),
        siteName: data.siteName || existing.siteName,
        adminEmail: data.adminEmail || existing.adminEmail,
        apiKey,
        apiKeyHash,
        dashboardIp: data.dashboardIp,
        status: 'online',
        lastSeen: new Date(),
      },
    }) as Site;
    
    logger.info({ siteId: site.id }, 'Site updated on registration');
  } else {
    // Создаём новый сайт
    site = await prisma.site.create({
      data: {
        siteUrl: data.siteUrl.replace(/\/$/, ''),
        siteName: data.siteName || new URL(data.siteUrl).hostname,
        adminEmail: data.adminEmail || '',
        apiKey,
        apiKeyHash,
        dashboardIp: data.dashboardIp,
        status: 'online',
        lastSeen: new Date(),
      },
    }) as Site;
    
    isNew = true;
    logger.info({ siteId: site.id }, 'New site registered');
  }
  
  // Сохраняем версию плагина если передана
  if (data.pluginVersion) {
    await prisma.siteVersion.upsert({
      where: { siteId: site.id },
      create: {
        siteId: site.id,
        pluginVersion: data.pluginVersion,
        lastSeenAt: new Date(),
        source: 'push',
        signatureOk: true,
      },
      update: {
        pluginVersion: data.pluginVersion,
        lastSeenAt: new Date(),
        source: 'push',
        signatureOk: true,
      },
    });
  }
  
  return { site, apiKey, isNew };
}

/**
 * Обновление статуса сайта
 */
export async function updateSiteStatus(
  siteUrl: string, 
  status: SiteStatus
): Promise<boolean> {
  const site = await findSiteByDomain(siteUrl);
  if (!site) return false;
  
  await prisma.site.update({
    where: { id: site.id },
    data: {
      status,
      lastSeen: new Date(),
    },
  });
  
  logger.debug({ siteId: site.id, status }, 'Site status updated');
  
  return true;
}

/**
 * Удаление сайта (перемещение в корзину)
 */
export async function deleteSite(
  siteId: number, 
  reason: string = 'removed_from_dashboard',
  adminId?: number
): Promise<void> {
  const site = await prisma.site.findUnique({
    where: { id: siteId },
  });
  
  if (!site) {
    throw new Error('Сайт не найден');
  }
  
  // Перемещаем в корзину
  await prisma.trash.create({
    data: {
      siteUrl: site.siteUrl,
      siteName: site.siteName,
      activeModules: site.activeModules,
      originalSiteId: site.id,
      deletedReason: reason,
      deletedByAdminId: adminId,
      restoreData: {
        adminEmail: site.adminEmail,
        dashboardIp: site.dashboardIp,
      },
    },
  });
  
  // Удаляем сайт
  await prisma.site.delete({
    where: { id: siteId },
  });
  
  logger.info({ siteId, reason }, 'Site moved to trash');
}

/**
 * Обновление версии плагина
 */
export async function updatePluginVersion(
  siteUrl: string,
  pluginVersion: string,
  source: 'push' | 'pull' = 'push'
): Promise<boolean> {
  const site = await findSiteByDomain(siteUrl);
  if (!site) return false;
  
  await prisma.siteVersion.upsert({
    where: { siteId: site.id },
    create: {
      siteId: site.id,
      pluginVersion,
      lastSeenAt: new Date(),
      source,
      signatureOk: true,
    },
    update: {
      pluginVersion,
      lastSeenAt: new Date(),
      source,
      signatureOk: true,
    },
  });
  
  logger.debug({ siteId: site.id, pluginVersion, source }, 'Plugin version updated');
  
  return true;
}

/**
 * Получение статистики по сайтам
 */
export async function getSiteStats(): Promise<{
  total: number;
  online: number;
  offline: number;
  maintenance: number;
}> {
  const [total, online, offline, maintenance] = await Promise.all([
    prisma.site.count(),
    prisma.site.count({ where: { status: 'online' } }),
    prisma.site.count({ where: { status: 'offline' } }),
    prisma.site.count({ where: { status: 'maintenance' } }),
  ]);
  
  return { total, online, offline, maintenance };
}

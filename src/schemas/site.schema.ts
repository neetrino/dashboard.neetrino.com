/**
 * Zod схемы для валидации данных сайтов
 */

import { z } from 'zod';

// ==========================================
// Схемы создания/обновления
// ==========================================

export const createSiteSchema = z.object({
  siteUrl: z
    .string()
    .min(1, 'URL сайта обязателен')
    .url('Введите корректный URL')
    .transform((url) => url.replace(/\/$/, '')), // Убираем trailing slash
  siteName: z
    .string()
    .min(1, 'Название сайта обязательно')
    .max(100, 'Название слишком длинное'),
  adminEmail: z
    .string()
    .email('Введите корректный email')
    .optional()
    .or(z.literal('')),
});

export const updateSiteSchema = z.object({
  siteName: z.string().min(1).max(100).optional(),
  adminEmail: z.string().email().optional().or(z.literal('')),
  status: z.enum(['online', 'offline', 'suspended', 'maintenance']).optional(),
  activeModules: z.string().optional(),
});

// ==========================================
// Схемы API запросов
// ==========================================

export const getSitesQuerySchema = z.object({
  page: z.coerce.number().min(1).default(1),
  perPage: z.coerce.number().min(1).max(100).default(20),
  search: z.string().optional(),
  status: z.enum(['online', 'offline', 'suspended', 'maintenance', 'all']).optional(),
});

export const siteIdParamSchema = z.object({
  id: z.coerce.number().int().positive(),
});

// ==========================================
// Схемы команд
// ==========================================

export const commandSchema = z.object({
  command: z.enum([
    'get_status',
    'get_info',
    'update_plugins',
    'update_plugin',
    'deactivate_plugin',
    'delete_plugin',
    'clear_cache',
    'backup_create',
    'optimize_db',
    'update_core',
    'security_scan',
    'performance_test',
    'maintenance_mode',
  ]),
  data: z.record(z.unknown()).optional(),
});

export const maintenanceModeSchema = z.object({
  mode: z.enum(['open', 'maintenance', 'closed']),
});

// ==========================================
// Схемы webhook'ов
// ==========================================

export const registerSiteSchema = z.object({
  site_url: z.string().url(),
  admin_email: z.string().email().optional(),
  site_title: z.string().optional(),
  plugin_version: z.string().optional(),
  temp_key: z.string().min(1),
});

export const pingSchema = z.object({
  site_url: z.string().url(),
  status: z.string().optional(),
});

export const versionPushSchema = z.object({
  site_url: z.string().url(),
  plugin_version: z.string().min(1),
  api_key: z.string().optional(),
});

// ==========================================
// Типы из схем
// ==========================================

export type CreateSiteInput = z.infer<typeof createSiteSchema>;
export type UpdateSiteInput = z.infer<typeof updateSiteSchema>;
export type GetSitesQuery = z.infer<typeof getSitesQuerySchema>;
export type CommandInput = z.infer<typeof commandSchema>;
export type RegisterSiteInput = z.infer<typeof registerSiteSchema>;
export type PingInput = z.infer<typeof pingSchema>;
export type VersionPushInput = z.infer<typeof versionPushSchema>;

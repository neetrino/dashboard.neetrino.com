/**
 * Zod схемы для настроек
 */

import { z } from 'zod';

export const getSettingSchema = z.object({
  key: z.string().min(1),
});

export const setSettingSchema = z.object({
  key: z.string().min(1).max(100),
  value: z.union([z.string(), z.number(), z.boolean(), z.record(z.unknown())]),
  type: z.enum(['string', 'integer', 'boolean', 'json']).default('string'),
});

export const dashboardSettingsSchema = z.object({
  refreshInterval: z.coerce.number().min(15).max(3600).default(30),
  defaultView: z.enum(['list', 'grid']).default('list'),
  commandTimeout: z.coerce.number().min(5).max(60).default(10),
  retryAttempts: z.coerce.number().min(1).max(10).default(3),
  minPluginVersion: z.string().regex(/^\d+(?:\.\d+){0,2}$/).optional().or(z.literal('')),
});

// Типы
export type GetSettingInput = z.infer<typeof getSettingSchema>;
export type SetSettingInput = z.infer<typeof setSettingSchema>;
export type DashboardSettings = z.infer<typeof dashboardSettingsSchema>;

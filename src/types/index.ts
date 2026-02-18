/**
 * Типы данных приложения
 */

// ==========================================
// Сайты
// ==========================================

export type SiteStatus = 'online' | 'offline' | 'suspended' | 'maintenance';

export interface Site {
  id: number;
  siteUrl: string;
  siteName: string;
  adminEmail: string;
  apiKey: string | null;
  apiKeyHash: string | null;
  dashboardIp: string | null;
  dateAdded: Date;
  activeModules: string | null;
  status: SiteStatus;
  createdAt: Date;
  lastSeen: Date | null;
  updatedAt: Date;
  // Связанные данные
  pluginVersion?: string | null;
  pluginLastSeen?: Date | null;
}

export interface SiteWithVersion extends Site {
  siteVersion?: {
    pluginVersion: string;
    lastSeenAt: Date;
    source: 'push' | 'pull';
    signatureOk: boolean;
  } | null;
}

export interface SiteListItem {
  id: number;
  siteUrl: string;
  siteName: string;
  status: SiteStatus;
  lastSeen: Date | null;
  pluginVersion: string | null;
  selected?: boolean;
}

// ==========================================
// Пагинация
// ==========================================

export interface Pagination {
  currentPage: number;
  perPage: number;
  totalItems: number;
  totalPages: number;
  hasNext: boolean;
  hasPrev: boolean;
}

export interface PaginatedResponse<T> {
  items: T[];
  pagination: Pagination;
}

// ==========================================
// API ответы
// ==========================================

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface ApiError {
  code: string;
  message: string;
  details?: Record<string, unknown>;
}

// ==========================================
// Команды на сайт
// ==========================================

export type SiteCommand =
  | 'get_status'
  | 'get_info'
  | 'update_plugins'
  | 'update_plugin'
  | 'deactivate_plugin'
  | 'delete_plugin'
  | 'clear_cache'
  | 'backup_create'
  | 'optimize_db'
  | 'update_core'
  | 'security_scan'
  | 'performance_test'
  | 'maintenance_mode';

export interface CommandPayload {
  command: SiteCommand;
  data?: Record<string, unknown>;
  apiKey: string;
}

export interface CommandResponse {
  success: boolean;
  command: SiteCommand;
  message: string;
  data?: Record<string, unknown>;
  timestamp: number;
}

// ==========================================
// Пользователи
// ==========================================

export type UserRole = 'admin' | 'moderator';

export interface User {
  id: number;
  username: string;
  email: string;
  role: UserRole;
  isActive: boolean;
  lastLogin: Date | null;
  createdAt: Date;
}

export interface SessionUser {
  id: number;
  username: string;
  email: string;
  role: UserRole;
}

// ==========================================
// Корзина
// ==========================================

export interface TrashItem {
  id: number;
  siteUrl: string;
  siteName: string;
  activeModules: string | null;
  originalSiteId: number | null;
  deletedAt: Date;
  deletedReason: string;
  deletedByAdminId: number | null;
  restoreData: Record<string, unknown> | null;
}

// ==========================================
// Настройки
// ==========================================

export type SettingType = 'string' | 'integer' | 'boolean' | 'json';

export interface SystemSetting {
  id: number;
  settingKey: string;
  settingValue: string | null;
  settingType: SettingType;
  description: string | null;
  isPublic: boolean;
}

// ==========================================
// Логи безопасности
// ==========================================

export type EventType =
  | 'login_attempt'
  | 'api_call'
  | 'rate_limit'
  | 'suspicious_activity'
  | 'admin_action';

export interface SecurityLogEntry {
  id: number;
  siteId: number | null;
  ipAddress: string;
  eventType: EventType;
  eventData: Record<string, unknown> | null;
  success: boolean;
  timestamp: Date;
  userAgent: string | null;
}

// ==========================================
// Фильтры и сортировка
// ==========================================

export interface SiteFilters {
  search?: string;
  status?: SiteStatus | 'all';
  page?: number;
  perPage?: number;
}

export type SortDirection = 'asc' | 'desc';

export interface SortOptions {
  field: string;
  direction: SortDirection;
}

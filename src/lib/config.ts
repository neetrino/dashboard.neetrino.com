/**
 * Конфигурация приложения
 * Все переменные окружения собраны в одном месте
 */

import { z } from 'zod';

// Схема валидации переменных окружения
const envSchema = z.object({
  // База данных
  DATABASE_URL: z.string().url(),
  
  // NextAuth
  NEXTAUTH_URL: z.string().url(),
  NEXTAUTH_SECRET: z.string().min(32),
  
  // Приложение
  APP_ENV: z.enum(['development', 'production', 'test']).default('development'),
  APP_NAME: z.string().default('Neetrino Dashboard'),
  APP_DOMAIN: z.string().default('localhost:3000'),
  
  // Безопасность
  API_KEY_SALT: z.string().default('neetrino_dashboard_salt_2025'),
  SESSION_MAX_AGE: z.coerce.number().default(86400), // 24 часа
  MAX_LOGIN_ATTEMPTS: z.coerce.number().default(3),
  LOCKOUT_DURATION: z.coerce.number().default(900), // 15 минут
  
  // API
  COMMAND_TIMEOUT: z.coerce.number().default(10000), // 10 секунд
  API_RATE_LIMIT: z.coerce.number().default(60), // запросов в минуту
  
  // Логирование
  LOG_LEVEL: z.enum(['debug', 'info', 'warn', 'error']).default('info'),
});

// Парсим и валидируем переменные окружения
function getConfig() {
  const parsed = envSchema.safeParse(process.env);
  
  if (!parsed.success) {
    console.error('❌ Ошибка конфигурации:');
    console.error(parsed.error.flatten().fieldErrors);
    
    // В development режиме продолжаем работу с дефолтными значениями
    if (process.env.NODE_ENV === 'development') {
      console.warn('⚠️ Используются дефолтные значения для разработки');
      return {
        DATABASE_URL: process.env.DATABASE_URL || 'mysql://root:@localhost:3306/dashbord_newsql1',
        NEXTAUTH_URL: process.env.NEXTAUTH_URL || 'http://localhost:3000',
        NEXTAUTH_SECRET: process.env.NEXTAUTH_SECRET || 'dev-secret-change-in-production-32chars',
        APP_ENV: 'development' as const,
        APP_NAME: 'Neetrino Dashboard',
        APP_DOMAIN: 'localhost:3000',
        API_KEY_SALT: 'neetrino_dashboard_salt_2025',
        SESSION_MAX_AGE: 86400,
        MAX_LOGIN_ATTEMPTS: 3,
        LOCKOUT_DURATION: 900,
        COMMAND_TIMEOUT: 10000,
        API_RATE_LIMIT: 60,
        LOG_LEVEL: 'debug' as const,
      };
    }
    
    throw new Error('Invalid environment configuration');
  }
  
  return parsed.data;
}

export const config = getConfig();

// Типы для экспорта
export type Config = typeof config;

// Хелперы
export const isDev = config.APP_ENV === 'development';
export const isProd = config.APP_ENV === 'production';
export const isTest = config.APP_ENV === 'test';

/**
 * Структурированное логирование
 * Использует pino для production и console для development
 */

import pino from 'pino';
import { config, isDev } from './config';

// Создаём логгер
const logger = pino({
  level: config.LOG_LEVEL,
  
  // Форматирование для development
  ...(isDev && {
    transport: {
      target: 'pino-pretty',
      options: {
        colorize: true,
        translateTime: 'SYS:standard',
        ignore: 'pid,hostname',
      },
    },
  }),
  
  // Базовые поля
  base: {
    env: config.APP_ENV,
  },
});

/**
 * Логгер для конкретного модуля
 */
export function createLogger(module: string) {
  return logger.child({ module });
}

/**
 * Логирование API запроса
 */
export function logApiRequest(
  method: string,
  path: string,
  status: number,
  duration: number,
  extra?: Record<string, unknown>
) {
  const log = logger.child({ type: 'api' });
  
  const level = status >= 500 ? 'error' : status >= 400 ? 'warn' : 'info';
  
  log[level]({
    method,
    path,
    status,
    duration: `${duration}ms`,
    ...extra,
  });
}

/**
 * Логирование события безопасности
 */
export function logSecurityEvent(
  event: string,
  ip: string,
  success: boolean,
  extra?: Record<string, unknown>
) {
  const log = logger.child({ type: 'security' });
  
  const level = success ? 'info' : 'warn';
  
  log[level]({
    event,
    ip,
    success,
    ...extra,
  });
}

/**
 * Логирование ошибки
 */
export function logError(error: Error, context?: Record<string, unknown>) {
  logger.error({
    error: {
      name: error.name,
      message: error.message,
      stack: error.stack,
    },
    ...context,
  });
}

export default logger;

/**
 * Структурированное логирование
 * pino без worker на Vercel (transport отключён), с pino-pretty локально
 */

import pino from 'pino';
import { config, isDev } from './config';

const isVercel = typeof process !== 'undefined' && process.env.VERCEL === '1';

const logger = pino({
  level: config.LOG_LEVEL,
  // Worker (pino-pretty) ломает сборку на Vercel — используем только локально
  ...(isDev && !isVercel && {
    transport: {
      target: 'pino-pretty',
      options: {
        colorize: true,
        translateTime: 'SYS:standard',
        ignore: 'pid,hostname',
      },
    },
  }),
  base: {
    env: config.APP_ENV,
  },
});

export function createLogger(module: string) {
  return logger.child({ module });
}

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

/**
 * Утилиты безопасности
 * Перенесено из PHP SecurityManager
 */

import bcrypt from 'bcryptjs';
import crypto from 'crypto';
import { config } from './config';
import { generateRandomString, secureCompare } from './utils';

/**
 * Генерация API ключа для сайта
 */
export function generateApiKey(): string {
  return 'ntr_dash_' + generateRandomString(32);
}

/**
 * Хеширование API ключа (SHA256 с солью)
 * Совместимо с PHP версией
 */
export function hashApiKey(apiKey: string): string {
  const salt = config.API_KEY_SALT;
  return crypto
    .createHash('sha256')
    .update(salt + apiKey + salt)
    .digest('hex');
}

/**
 * Проверка API ключа
 */
export function verifyApiKey(providedKey: string, storedHash: string): boolean {
  if (!providedKey || !storedHash) {
    return false;
  }
  
  const computedHash = hashApiKey(providedKey);
  return secureCompare(computedHash, storedHash);
}

/**
 * Хеширование пароля (bcrypt)
 */
export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, 10);
}

/**
 * Проверка пароля
 */
export async function verifyPassword(password: string, hash: string): Promise<boolean> {
  return bcrypt.compare(password, hash);
}

/**
 * Проверка на блокировку пользователя
 */
export function isUserLocked(lockedUntil: Date | null): boolean {
  if (!lockedUntil) return false;
  return new Date() < lockedUntil;
}

/**
 * Вычисление времени блокировки
 */
export function calculateLockUntil(): Date {
  return new Date(Date.now() + config.LOCKOUT_DURATION * 1000);
}

/**
 * Проверка превышения лимита попыток входа
 */
export function isLoginAttemptsExceeded(attempts: number): boolean {
  return attempts >= config.MAX_LOGIN_ATTEMPTS;
}

/**
 * Генерация CSRF токена
 */
export function generateCsrfToken(): string {
  return crypto.randomBytes(32).toString('hex');
}

/**
 * Проверка CSRF токена
 */
export function verifyCsrfToken(token: string, storedToken: string): boolean {
  if (!token || !storedToken) return false;
  return secureCompare(token, storedToken);
}

/**
 * Генерация nonce для API запросов
 */
export function generateNonce(): string {
  return crypto.randomBytes(16).toString('hex') + '-' + Date.now();
}

/**
 * Валидация timestamp запроса (защита от replay атак)
 * Допуск ±5 минут
 */
export function isTimestampValid(timestamp: number, toleranceSeconds: number = 300): boolean {
  const now = Math.floor(Date.now() / 1000);
  const diff = Math.abs(now - timestamp);
  return diff <= toleranceSeconds;
}

/**
 * Создание HMAC подписи для API запроса
 */
export function createHmacSignature(data: string, secret: string): string {
  return crypto
    .createHmac('sha256', secret)
    .update(data)
    .digest('hex');
}

/**
 * Проверка HMAC подписи
 */
export function verifyHmacSignature(data: string, signature: string, secret: string): boolean {
  const expectedSignature = createHmacSignature(data, secret);
  return secureCompare(expectedSignature, signature);
}

/**
 * Санитизация входных данных
 */
export function sanitizeInput(input: string): string {
  return input
    .trim()
    .replace(/[<>]/g, '') // Удаляем HTML-подобные символы
    .slice(0, 10000); // Ограничиваем длину
}

/**
 * Проверка силы пароля
 */
export function checkPasswordStrength(password: string): {
  valid: boolean;
  errors: string[];
} {
  const errors: string[] = [];
  
  if (password.length < 6) {
    errors.push('Пароль должен содержать минимум 6 символов');
  }
  
  if (password.length > 128) {
    errors.push('Пароль слишком длинный');
  }
  
  return {
    valid: errors.length === 0,
    errors,
  };
}

/**
 * Сервис авторизации
 * Бизнес-логика перенесена из PHP Auth.php
 */

import prisma from '@/lib/prisma';
import { 
  verifyPassword, 
  hashPassword, 
  isUserLocked, 
  isLoginAttemptsExceeded,
  calculateLockUntil,
  checkPasswordStrength 
} from '@/lib/security';
import { createLogger, logSecurityEvent } from '@/lib/logger';
import type { User, SessionUser } from '@/types';

const logger = createLogger('auth.service');

/**
 * Аутентификация пользователя
 */
export async function authenticateUser(
  username: string,
  password: string,
  ip: string
): Promise<{
  success: boolean;
  user?: SessionUser;
  error?: string;
}> {
  // Ищем пользователя
  const user = await prisma.adminUser.findUnique({
    where: { username },
  });
  
  if (!user) {
    logSecurityEvent('login_attempt', ip, false, { username, reason: 'user_not_found' });
    return { success: false, error: 'Неверный логин или пароль' };
  }
  
  // Проверяем блокировку
  if (isUserLocked(user.lockedUntil)) {
    const unlockTime = user.lockedUntil!.toLocaleTimeString('ru-RU', {
      hour: '2-digit',
      minute: '2-digit',
    });
    
    logSecurityEvent('login_attempt', ip, false, { 
      username, 
      reason: 'account_locked',
      lockedUntil: user.lockedUntil 
    });
    
    return { 
      success: false, 
      error: `Аккаунт заблокирован до ${unlockTime}` 
    };
  }
  
  // Проверяем активность аккаунта
  if (!user.isActive) {
    logSecurityEvent('login_attempt', ip, false, { username, reason: 'account_disabled' });
    return { success: false, error: 'Аккаунт отключен' };
  }
  
  // Проверяем пароль
  const isValidPassword = await verifyPassword(password, user.passwordHash);
  
  if (!isValidPassword) {
    // Увеличиваем счётчик неудачных попыток
    await incrementFailedAttempts(user.id);
    
    logSecurityEvent('login_attempt', ip, false, { 
      username, 
      reason: 'invalid_password',
      failedAttempts: user.failedAttempts + 1 
    });
    
    return { success: false, error: 'Неверный логин или пароль' };
  }
  
  // Успешный вход - сбрасываем счётчик и обновляем last_login
  await prisma.adminUser.update({
    where: { id: user.id },
    data: {
      failedAttempts: 0,
      lockedUntil: null,
      lastLogin: new Date(),
    },
  });
  
  logSecurityEvent('login_attempt', ip, true, { username });
  logger.info({ userId: user.id, username }, 'User logged in');
  
  return {
    success: true,
    user: {
      id: user.id,
      username: user.username,
      email: user.email,
      role: user.role as 'admin' | 'moderator',
    },
  };
}

/**
 * Увеличение счётчика неудачных попыток
 */
async function incrementFailedAttempts(userId: number): Promise<void> {
  const user = await prisma.adminUser.update({
    where: { id: userId },
    data: {
      failedAttempts: { increment: 1 },
    },
  });
  
  // Блокируем если превышен лимит
  if (isLoginAttemptsExceeded(user.failedAttempts)) {
    await prisma.adminUser.update({
      where: { id: userId },
      data: {
        lockedUntil: calculateLockUntil(),
      },
    });
    
    logger.warn({ userId }, 'User account locked due to failed attempts');
  }
}

/**
 * Получение пользователя по ID
 */
export async function getUserById(id: number): Promise<User | null> {
  const user = await prisma.adminUser.findUnique({
    where: { id },
    select: {
      id: true,
      username: true,
      email: true,
      role: true,
      isActive: true,
      lastLogin: true,
      createdAt: true,
    },
  });
  
  if (!user) return null;
  
  return {
    ...user,
    role: user.role as 'admin' | 'moderator',
  };
}

/**
 * Смена пароля
 */
export async function changePassword(
  userId: number,
  currentPassword: string,
  newPassword: string
): Promise<{ success: boolean; error?: string }> {
  const user = await prisma.adminUser.findUnique({
    where: { id: userId },
  });
  
  if (!user) {
    return { success: false, error: 'Пользователь не найден' };
  }
  
  // Проверяем текущий пароль
  const isValid = await verifyPassword(currentPassword, user.passwordHash);
  if (!isValid) {
    return { success: false, error: 'Неверный текущий пароль' };
  }
  
  // Проверяем силу нового пароля
  const strength = checkPasswordStrength(newPassword);
  if (!strength.valid) {
    return { success: false, error: strength.errors[0] };
  }
  
  // Хешируем и сохраняем новый пароль
  const newPasswordHash = await hashPassword(newPassword);
  
  await prisma.adminUser.update({
    where: { id: userId },
    data: { passwordHash: newPasswordHash },
  });
  
  logger.info({ userId }, 'Password changed');
  
  return { success: true };
}

/**
 * Создание нового пользователя
 */
export async function createUser(data: {
  username: string;
  email: string;
  password: string;
  role?: 'admin' | 'moderator';
}): Promise<User> {
  // Проверяем существование
  const existing = await prisma.adminUser.findFirst({
    where: {
      OR: [
        { username: data.username },
        { email: data.email },
      ],
    },
  });
  
  if (existing) {
    throw new Error('Пользователь с таким логином или email уже существует');
  }
  
  // Проверяем пароль
  const strength = checkPasswordStrength(data.password);
  if (!strength.valid) {
    throw new Error(strength.errors[0]);
  }
  
  // Создаём пользователя
  const passwordHash = await hashPassword(data.password);
  
  const user = await prisma.adminUser.create({
    data: {
      username: data.username,
      email: data.email,
      passwordHash,
      role: data.role || 'admin',
    },
    select: {
      id: true,
      username: true,
      email: true,
      role: true,
      isActive: true,
      lastLogin: true,
      createdAt: true,
    },
  });
  
  logger.info({ userId: user.id, username: user.username }, 'User created');
  
  return {
    ...user,
    role: user.role as 'admin' | 'moderator',
  };
}

/**
 * Zod схемы для авторизации
 */

import { z } from 'zod';

export const loginSchema = z.object({
  username: z
    .string()
    .min(1, 'Введите логин')
    .max(50, 'Логин слишком длинный'),
  password: z
    .string()
    .min(1, 'Введите пароль'),
});

export const changePasswordSchema = z.object({
  currentPassword: z
    .string()
    .min(1, 'Введите текущий пароль'),
  newPassword: z
    .string()
    .min(6, 'Пароль должен содержать минимум 6 символов')
    .max(128, 'Пароль слишком длинный'),
  confirmPassword: z
    .string()
    .min(1, 'Подтвердите пароль'),
}).refine((data) => data.newPassword === data.confirmPassword, {
  message: 'Пароли не совпадают',
  path: ['confirmPassword'],
});

export const createUserSchema = z.object({
  username: z
    .string()
    .min(3, 'Логин должен содержать минимум 3 символа')
    .max(50, 'Логин слишком длинный')
    .regex(/^[a-zA-Z0-9_]+$/, 'Логин может содержать только буквы, цифры и _'),
  email: z
    .string()
    .email('Введите корректный email'),
  password: z
    .string()
    .min(6, 'Пароль должен содержать минимум 6 символов')
    .max(128, 'Пароль слишком длинный'),
  role: z.enum(['admin', 'moderator']).default('admin'),
});

// Типы
export type LoginInput = z.infer<typeof loginSchema>;
export type ChangePasswordInput = z.infer<typeof changePasswordSchema>;
export type CreateUserInput = z.infer<typeof createUserSchema>;

/**
 * Prisma Client - Singleton для Next.js
 * Предотвращает создание множества подключений в dev режиме
 */

import { PrismaClient } from '@prisma/client';

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
};

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({
    log:
      process.env.NODE_ENV === 'development'
        ? ['query', 'error', 'warn']
        : ['error'],
  });

if (process.env.NODE_ENV !== 'production') {
  globalForPrisma.prisma = prisma;
}

// Проверка подключения при инициализации (только в dev)
if (process.env.NODE_ENV === 'development') {
  prisma.$connect().catch((error) => {
    console.error('❌ Ошибка подключения к базе данных:', error.message);
    console.error('💡 Убедитесь, что MySQL запущен и DATABASE_URL корректна');
  });
}

export default prisma;

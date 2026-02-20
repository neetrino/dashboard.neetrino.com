/**
 * GET /api/diagnosis
 * Проверка состояния системы (БД, окружение) — как в старом дашборде
 */

import { NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import prisma from '@/lib/prisma';
import { logApiRequest, logError } from '@/lib/logger';

export async function GET() {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('GET', '/api/diagnosis', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const checks: { name: string; status: 'ok' | 'error'; message?: string }[] = [];
    let dbOk = false;

    try {
      await prisma.$queryRaw`SELECT 1`;
      dbOk = true;
      checks.push({ name: 'База данных', status: 'ok', message: 'Подключение активно' });
    } catch (e) {
      const msg = e instanceof Error ? e.message : 'Unknown';
      checks.push({ name: 'База данных', status: 'error', message: msg });
    }

    if (dbOk) {
      try {
        const [sitesCount, trashCount] = await Promise.all([
          prisma.site.count(),
          prisma.trash.count(),
        ]);
        checks.push({
          name: 'Таблицы',
          status: 'ok',
          message: `Сайтов: ${sitesCount}, в корзине: ${trashCount}`,
        });
      } catch (e) {
        const msg = e instanceof Error ? e.message : 'Unknown';
        checks.push({ name: 'Таблицы', status: 'error', message: msg });
      }
    }

    const nodeVersion = process.version;
    checks.push({ name: 'Среда', status: 'ok', message: `Node.js ${nodeVersion}` });

    const allOk = checks.every((c) => c.status === 'ok');
    logApiRequest('GET', '/api/diagnosis', 200, Date.now() - startTime);

    return NextResponse.json({
      success: allOk,
      checks,
      serverTime: new Date().toISOString(),
    });
  } catch (error) {
    logError(error as Error, { route: 'GET /api/diagnosis' });
    logApiRequest('GET', '/api/diagnosis', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

/**
 * GET /api/sites/stats — статистика по сайтам (всего, онлайн, офлайн, обслуживание)
 */

import { NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getSiteStats } from '@/services/sites.service';
import { logApiRequest, logError } from '@/lib/logger';

export async function GET() {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('GET', '/api/sites/stats', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const stats = await getSiteStats();
    logApiRequest('GET', '/api/sites/stats', 200, Date.now() - startTime);
    return NextResponse.json(stats);
  } catch (error) {
    logError(error as Error, { route: 'GET /api/sites/stats' });
    logApiRequest('GET', '/api/sites/stats', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

/**
 * POST /api/trash/restore-all
 * Восстановление всех сайтов из корзины (как в старом дашборде)
 */

import { NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { restoreAllFromTrash } from '@/services/trash.service';
import { logApiRequest, logError } from '@/lib/logger';

export async function POST() {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('POST', '/api/trash/restore-all', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const result = await restoreAllFromTrash();
    logApiRequest('POST', '/api/trash/restore-all', 200, Date.now() - startTime, result);

    return NextResponse.json({
      success: true,
      message: `Восстановлено: ${result.restored}, пропущено (уже есть): ${result.skipped}`,
      restored: result.restored,
      skipped: result.skipped,
      errors: result.errors,
    });
  } catch (error) {
    logError(error as Error, { route: 'POST /api/trash/restore-all' });
    logApiRequest('POST', '/api/trash/restore-all', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

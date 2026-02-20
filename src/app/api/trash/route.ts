/**
 * GET /api/trash — список элементов корзины
 */

import { NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getTrashItems } from '@/services/trash.service';
import { logApiRequest, logError } from '@/lib/logger';

export async function GET() {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('GET', '/api/trash', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    const items = await getTrashItems();
    logApiRequest('GET', '/api/trash', 200, Date.now() - startTime, { count: items.length });
    return NextResponse.json({ items });
  } catch (error) {
    logError(error as Error, { route: 'GET /api/trash' });
    logApiRequest('GET', '/api/trash', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

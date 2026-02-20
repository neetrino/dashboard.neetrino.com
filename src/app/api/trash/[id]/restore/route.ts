/**
 * POST /api/trash/[id]/restore — восстановить сайт из корзины
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { restoreFromTrash } from '@/services/trash.service';
import { logApiRequest, logError } from '@/lib/logger';

export async function POST(
  _request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('POST', '/api/trash/[id]/restore', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    const { id } = await params;
    const trashId = parseInt(id, 10);
    if (Number.isNaN(trashId)) {
      return NextResponse.json({ error: 'Invalid id' }, { status: 400 });
    }
    const result = await restoreFromTrash(trashId);
    if (!result.success) {
      return NextResponse.json(
        { success: false, error: result.error },
        { status: 400 }
      );
    }
    logApiRequest('POST', '/api/trash/[id]/restore', 200, Date.now() - startTime);
    return NextResponse.json({ success: true, siteId: result.siteId });
  } catch (error) {
    logError(error as Error, { route: 'POST /api/trash/[id]/restore' });
    logApiRequest('POST', '/api/trash/[id]/restore', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

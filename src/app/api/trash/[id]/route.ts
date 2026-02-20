/**
 * DELETE /api/trash/[id] — удалить из корзины навсегда
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { permanentlyDelete } from '@/services/trash.service';
import { logApiRequest, logError } from '@/lib/logger';

export async function DELETE(
  _request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('DELETE', '/api/trash/[id]', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    const { id } = await params;
    const trashId = parseInt(id, 10);
    if (Number.isNaN(trashId)) {
      return NextResponse.json({ error: 'Invalid id' }, { status: 400 });
    }
    await permanentlyDelete(trashId);
    logApiRequest('DELETE', '/api/trash/[id]', 200, Date.now() - startTime);
    return NextResponse.json({ success: true });
  } catch (error) {
    logError(error as Error, { route: 'DELETE /api/trash/[id]' });
    logApiRequest('DELETE', '/api/trash/[id]', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

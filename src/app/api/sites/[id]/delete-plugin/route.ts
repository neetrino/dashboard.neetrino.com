/**
 * POST /api/sites/[id]/delete-plugin
 * Удаление плагина на сайте (команда на WP) и перемещение записи в корзину
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getSiteById, deleteSite } from '@/services/sites.service';
import { sendCommand } from '@/services/command.service';
import { siteIdParamSchema } from '@/schemas/site.schema';
import { logApiRequest, logError } from '@/lib/logger';

type RouteParams = { params: Promise<{ id: string }> };

export async function POST(request: NextRequest, { params }: RouteParams) {
  const startTime = Date.now();
  const { id } = await params;

  try {
    const session = await auth();
    if (!session) {
      logApiRequest('POST', `/api/sites/${id}/delete-plugin`, 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const validated = siteIdParamSchema.safeParse({ id });
    if (!validated.success) {
      return NextResponse.json({ error: 'Invalid ID' }, { status: 400 });
    }

    const site = await getSiteById(validated.data.id);
    if (!site) {
      return NextResponse.json({ error: 'Site not found' }, { status: 404 });
    }

    if (!site.apiKey) {
      return NextResponse.json({ error: 'Site has no API key' }, { status: 400 });
    }

    // Сначала отправляем команду удаления плагина на сайт
    try {
      await sendCommand(site, 'delete_plugin');
    } catch (cmdError) {
      // Даже при ошибке команды перемещаем сайт в корзину (сайт может быть недоступен)
      logError(cmdError as Error, { route: `POST /api/sites/${id}/delete-plugin` });
    }

    const adminId = parseInt(session.user.id, 10);
    await deleteSite(validated.data.id, 'plugin_deleted', adminId);

    logApiRequest('POST', `/api/sites/${id}/delete-plugin`, 200, Date.now() - startTime);
    return NextResponse.json({ success: true, message: 'Плагин удалён, сайт перемещён в корзину' });
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error';
    logError(error as Error, { route: `POST /api/sites/${id}/delete-plugin` });
    logApiRequest('POST', `/api/sites/${id}/delete-plugin`, 500, Date.now() - startTime);
    return NextResponse.json({ success: false, error: message }, { status: 500 });
  }
}

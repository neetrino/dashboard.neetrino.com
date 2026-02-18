/**
 * API Route для отправки команд на сайт
 * POST /api/sites/[id]/command
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getSiteById, updateSiteStatus, updatePluginVersion } from '@/services/sites.service';
import { sendCommand } from '@/services/command.service';
import { commandSchema, siteIdParamSchema } from '@/schemas/site.schema';
import { logApiRequest, logError } from '@/lib/logger';

type RouteParams = { params: Promise<{ id: string }> };

export async function POST(
  request: NextRequest,
  { params }: RouteParams
) {
  const startTime = Date.now();
  const { id } = await params;
  
  try {
    // Проверка авторизации
    const session = await auth();
    if (!session) {
      logApiRequest('POST', `/api/sites/${id}/command`, 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    // Валидация ID
    const validatedId = siteIdParamSchema.safeParse({ id });
    if (!validatedId.success) {
      return NextResponse.json({ error: 'Invalid site ID' }, { status: 400 });
    }
    
    // Получение сайта
    const site = await getSiteById(validatedId.data.id);
    if (!site) {
      return NextResponse.json({ error: 'Site not found' }, { status: 404 });
    }
    
    if (!site.apiKey) {
      return NextResponse.json({ error: 'Site has no API key' }, { status: 400 });
    }
    
    // Валидация команды
    const body = await request.json();
    const validatedCommand = commandSchema.safeParse(body);
    
    if (!validatedCommand.success) {
      return NextResponse.json(
        { error: 'Invalid command', details: validatedCommand.error.flatten() },
        { status: 400 }
      );
    }
    
    const { command, data = {} } = validatedCommand.data;
    
    // Отправка команды
    const result = await sendCommand(site, command, data);
    
    // Обновляем статус сайта при успешной команде
    if (result.success) {
      await updateSiteStatus(site.siteUrl, 'online');
      
      // Если в ответе есть версия плагина - сохраняем её
      if (result.data?.plugin_version) {
        await updatePluginVersion(
          site.siteUrl,
          result.data.plugin_version as string,
          'pull'
        );
      }
    }
    
    logApiRequest('POST', `/api/sites/${id}/command`, 200, Date.now() - startTime, {
      command,
      success: result.success,
    });
    
    return NextResponse.json(result);
    
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error';
    
    // Обновляем статус сайта на offline при ошибке
    try {
      const validatedId = siteIdParamSchema.safeParse({ id });
      if (validatedId.success) {
        const site = await getSiteById(validatedId.data.id);
        if (site) {
          await updateSiteStatus(site.siteUrl, 'offline');
        }
      }
    } catch {
      // Игнорируем ошибки обновления статуса
    }
    
    logError(error as Error, { route: `POST /api/sites/${id}/command` });
    logApiRequest('POST', `/api/sites/${id}/command`, 500, Date.now() - startTime);
    
    return NextResponse.json(
      { 
        success: false, 
        error: message,
        message: message 
      },
      { status: 500 }
    );
  }
}

/**
 * Webhook для push версии плагина
 * POST /api/webhook/version-push
 */

import { NextRequest, NextResponse } from 'next/server';
import { findSiteByDomain, updatePluginVersion } from '@/services/sites.service';
import { versionPushSchema } from '@/schemas/site.schema';
import { verifyApiKey } from '@/lib/security';
import { getClientIp } from '@/lib/utils';
import { logApiRequest, logError } from '@/lib/logger';

export async function POST(request: NextRequest) {
  const startTime = Date.now();
  const ip = getClientIp(request.headers);
  
  try {
    // Парсинг данных
    const contentType = request.headers.get('content-type') || '';
    let body: Record<string, string>;
    
    if (contentType.includes('application/json')) {
      body = await request.json();
    } else {
      const formData = await request.formData();
      body = Object.fromEntries(formData.entries()) as Record<string, string>;
    }
    
    // Валидация
    const validated = versionPushSchema.safeParse(body);
    
    if (!validated.success) {
      logApiRequest('POST', '/api/webhook/version-push', 400, Date.now() - startTime);
      return NextResponse.json(
        { success: false, error: 'site_url and plugin_version are required' },
        { status: 400 }
      );
    }
    
    const { site_url, plugin_version, api_key } = validated.data;
    
    // Находим сайт
    const site = await findSiteByDomain(site_url);
    if (!site) {
      return NextResponse.json(
        { success: false, error: 'Site not found' },
        { status: 404 }
      );
    }
    
    // Проверяем API ключ если передан
    if (api_key && site.apiKeyHash) {
      const isValid = verifyApiKey(api_key, site.apiKeyHash);
      if (!isValid) {
        return NextResponse.json(
          { success: false, error: 'Unauthorized' },
          { status: 401 }
        );
      }
    }
    
    // Обновляем версию
    await updatePluginVersion(site_url, plugin_version, 'push');
    
    logApiRequest('POST', '/api/webhook/version-push', 200, Date.now() - startTime, {
      siteId: site.id,
      pluginVersion: plugin_version,
    });
    
    return NextResponse.json({
      success: true,
      message: 'Version updated',
      site_id: site.id,
      plugin_version,
    });
    
  } catch (error) {
    logError(error as Error, { route: 'POST /api/webhook/version-push' });
    logApiRequest('POST', '/api/webhook/version-push', 500, Date.now() - startTime);
    
    return NextResponse.json(
      { success: false, error: 'Failed to store version' },
      { status: 500 }
    );
  }
}

export async function OPTIONS() {
  return new NextResponse(null, {
    status: 200,
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
    },
  });
}

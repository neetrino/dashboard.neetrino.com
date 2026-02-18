/**
 * Webhook для регистрации сайта
 * POST /api/webhook/register
 * 
 * Вызывается плагином Neetrino при установке/активации
 */

import { NextRequest, NextResponse } from 'next/server';
import { registerSite } from '@/services/sites.service';
import { registerSiteSchema } from '@/schemas/site.schema';
import { getClientIp } from '@/lib/utils';
import { logApiRequest, logError, logSecurityEvent } from '@/lib/logger';
import { config } from '@/lib/config';

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
      // Form data (как в PHP версии)
      const formData = await request.formData();
      body = Object.fromEntries(formData.entries()) as Record<string, string>;
    }
    
    // Валидация
    const validated = registerSiteSchema.safeParse(body);
    
    if (!validated.success) {
      logApiRequest('POST', '/api/webhook/register', 400, Date.now() - startTime);
      return NextResponse.json(
        { success: false, error: 'Invalid data', details: validated.error.flatten() },
        { status: 400 }
      );
    }
    
    const { site_url, admin_email, site_title, plugin_version, temp_key } = validated.data;
    
    // Регистрация сайта
    const result = await registerSite({
      siteUrl: site_url,
      siteName: site_title,
      adminEmail: admin_email,
      pluginVersion: plugin_version,
      dashboardIp: ip,
      dashboardDomain: config.APP_DOMAIN,
    });
    
    logSecurityEvent('site_registration', ip, true, {
      siteUrl: site_url,
      isNew: result.isNew,
    });
    
    logApiRequest('POST', '/api/webhook/register', 200, Date.now() - startTime, {
      siteId: result.site.id,
      isNew: result.isNew,
    });
    
    // Пытаемся отправить конфигурацию плагину
    let configSent = false;
    try {
      const configUrl = `${site_url.replace(/\/$/, '')}/wp-json/neetrino/v1/update-dashboard-config`;
      const configData = new URLSearchParams({
        dashboard_ip: ip,
        dashboard_domain: config.APP_DOMAIN,
        api_key: result.apiKey,
        temp_key: temp_key,
        registration_status: 'registered',
      });
      
      const configResponse = await fetch(configUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: configData.toString(),
        signal: AbortSignal.timeout(10000),
      });
      
      configSent = configResponse.ok;
    } catch {
      // Конфигурация не отправлена, но регистрация успешна
    }
    
    return NextResponse.json({
      status: 'success',
      message: 'Site registered successfully',
      dashboard_ip: ip,
      dashboard_domain: config.APP_DOMAIN,
      api_key: result.apiKey,
      config_sent: configSent,
    });
    
  } catch (error) {
    logError(error as Error, { route: 'POST /api/webhook/register' });
    logSecurityEvent('site_registration', ip, false, {
      error: error instanceof Error ? error.message : 'Unknown',
    });
    logApiRequest('POST', '/api/webhook/register', 500, Date.now() - startTime);
    
    return NextResponse.json(
      { success: false, error: 'Registration failed' },
      { status: 500 }
    );
  }
}

// Разрешаем CORS для вебхуков
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

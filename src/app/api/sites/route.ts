/**
 * API Routes для работы с сайтами
 * GET /api/sites - список сайтов
 * POST /api/sites - создание сайта
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getSites, createSite } from '@/services/sites.service';
import { getSitesQuerySchema, createSiteSchema } from '@/schemas/site.schema';
import { logApiRequest, logError } from '@/lib/logger';

export async function GET(request: NextRequest) {
  const startTime = Date.now();
  
  try {
    // Проверка авторизации
    const session = await auth();
    if (!session) {
      logApiRequest('GET', '/api/sites', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    // Парсинг и валидация параметров
    const searchParams = Object.fromEntries(request.nextUrl.searchParams);
    const validatedParams = getSitesQuerySchema.safeParse(searchParams);
    
    if (!validatedParams.success) {
      logApiRequest('GET', '/api/sites', 400, Date.now() - startTime, {
        error: validatedParams.error.flatten(),
      });
      return NextResponse.json(
        { error: 'Invalid parameters', details: validatedParams.error.flatten() },
        { status: 400 }
      );
    }
    
    // Получение сайтов
    const result = await getSites(validatedParams.data);
    
    logApiRequest('GET', '/api/sites', 200, Date.now() - startTime, {
      count: result.items.length,
      page: result.pagination.currentPage,
    });
    
    return NextResponse.json(result);
    
  } catch (error) {
    logError(error as Error, { route: 'GET /api/sites' });
    logApiRequest('GET', '/api/sites', 500, Date.now() - startTime);
    
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  const startTime = Date.now();
  
  try {
    // Проверка авторизации
    const session = await auth();
    if (!session) {
      logApiRequest('POST', '/api/sites', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    // Парсинг и валидация тела запроса
    const body = await request.json();
    const validatedData = createSiteSchema.safeParse(body);
    
    if (!validatedData.success) {
      logApiRequest('POST', '/api/sites', 400, Date.now() - startTime, {
        error: validatedData.error.flatten(),
      });
      return NextResponse.json(
        { error: 'Validation error', details: validatedData.error.flatten() },
        { status: 400 }
      );
    }
    
    // Создание сайта
    const site = await createSite(validatedData.data);
    
    logApiRequest('POST', '/api/sites', 201, Date.now() - startTime, {
      siteId: site.id,
    });
    
    return NextResponse.json(site, { status: 201 });
    
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error';
    
    // Проверяем на дубликат
    if (message.includes('уже существует')) {
      logApiRequest('POST', '/api/sites', 409, Date.now() - startTime);
      return NextResponse.json({ error: message }, { status: 409 });
    }
    
    logError(error as Error, { route: 'POST /api/sites' });
    logApiRequest('POST', '/api/sites', 500, Date.now() - startTime);
    
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}

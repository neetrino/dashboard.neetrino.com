/**
 * API Routes для работы с конкретным сайтом
 * GET /api/sites/[id] - получение сайта
 * PATCH /api/sites/[id] - обновление сайта
 * DELETE /api/sites/[id] - удаление сайта
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getSiteById, deleteSite } from '@/services/sites.service';
import prisma from '@/lib/prisma';
import { siteIdParamSchema, updateSiteSchema } from '@/schemas/site.schema';
import { logApiRequest, logError } from '@/lib/logger';

type RouteParams = { params: Promise<{ id: string }> };

export async function GET(
  request: NextRequest,
  { params }: RouteParams
) {
  const startTime = Date.now();
  const { id } = await params;
  
  try {
    const session = await auth();
    if (!session) {
      logApiRequest('GET', `/api/sites/${id}`, 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    const validated = siteIdParamSchema.safeParse({ id });
    if (!validated.success) {
      return NextResponse.json({ error: 'Invalid ID' }, { status: 400 });
    }
    
    const site = await getSiteById(validated.data.id);
    
    if (!site) {
      logApiRequest('GET', `/api/sites/${id}`, 404, Date.now() - startTime);
      return NextResponse.json({ error: 'Site not found' }, { status: 404 });
    }
    
    logApiRequest('GET', `/api/sites/${id}`, 200, Date.now() - startTime);
    return NextResponse.json(site);
    
  } catch (error) {
    logError(error as Error, { route: `GET /api/sites/${id}` });
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
  }
}

export async function PATCH(
  request: NextRequest,
  { params }: RouteParams
) {
  const startTime = Date.now();
  const { id } = await params;
  
  try {
    const session = await auth();
    if (!session) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    const validated = siteIdParamSchema.safeParse({ id });
    if (!validated.success) {
      return NextResponse.json({ error: 'Invalid ID' }, { status: 400 });
    }
    
    const body = await request.json();
    const validatedData = updateSiteSchema.safeParse(body);
    
    if (!validatedData.success) {
      return NextResponse.json(
        { error: 'Validation error', details: validatedData.error.flatten() },
        { status: 400 }
      );
    }
    
    const site = await prisma.site.update({
      where: { id: validated.data.id },
      data: validatedData.data,
    });
    
    logApiRequest('PATCH', `/api/sites/${id}`, 200, Date.now() - startTime);
    return NextResponse.json(site);
    
  } catch (error) {
    logError(error as Error, { route: `PATCH /api/sites/${id}` });
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
  }
}

export async function DELETE(
  request: NextRequest,
  { params }: RouteParams
) {
  const startTime = Date.now();
  const { id } = await params;
  
  try {
    const session = await auth();
    if (!session) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    
    const validated = siteIdParamSchema.safeParse({ id });
    if (!validated.success) {
      return NextResponse.json({ error: 'Invalid ID' }, { status: 400 });
    }
    
    const adminId = parseInt(session.user.id, 10);
    
    await deleteSite(validated.data.id, 'removed_from_dashboard', adminId);
    
    logApiRequest('DELETE', `/api/sites/${id}`, 200, Date.now() - startTime);
    return NextResponse.json({ success: true });
    
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Unknown error';
    
    if (message.includes('не найден')) {
      return NextResponse.json({ error: message }, { status: 404 });
    }
    
    logError(error as Error, { route: `DELETE /api/sites/${id}` });
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
  }
}

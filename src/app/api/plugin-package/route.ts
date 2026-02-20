/**
 * GET /api/plugin-package?api_key=...
 * Раздача ZIP плагина для обновления на сайте. Доступ только с валидным api_key сайта.
 */

import { NextRequest, NextResponse } from 'next/server';
import prisma from '@/lib/prisma';
import { getPluginPackagePath } from '@/services/settings.service';
import { createLogger } from '@/lib/logger';
import { readFile } from 'fs/promises';
import { join } from 'path';

const logger = createLogger('plugin-package');

export async function GET(request: NextRequest) {
  const apiKey = request.nextUrl.searchParams.get('api_key');
  if (!apiKey) {
    return NextResponse.json({ error: 'api_key required' }, { status: 400 });
  }

  try {
    const site = await prisma.site.findFirst({
      where: { apiKey },
    });
    if (!site) {
      logger.warn('Plugin package download attempted with invalid api_key');
      return NextResponse.json({ error: 'Invalid api_key' }, { status: 403 });
    }

    const relativePath = await getPluginPackagePath();
    if (!relativePath) {
      return NextResponse.json({ error: 'Plugin package not configured' }, { status: 404 });
    }

    const absolutePath = join(process.cwd(), relativePath);
    const buffer = await readFile(absolutePath);

    return new NextResponse(buffer, {
      status: 200,
      headers: {
        'Content-Type': 'application/zip',
        'Content-Disposition': 'attachment; filename="neetrino.zip"',
        'Content-Length': String(buffer.length),
      },
    });
  } catch (error) {
    if ((error as NodeJS.ErrnoException)?.code === 'ENOENT') {
      return NextResponse.json({ error: 'Plugin package file not found' }, { status: 404 });
    }
    logger.error(error as Error, 'Failed to serve plugin package');
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
  }
}

/**
 * POST /api/settings/plugin-package — загрузка ZIP плагина
 * GET  /api/settings/plugin-package — статус (есть ли файл, размер)
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { getPluginPackagePath, setPluginPackagePath } from '@/services/settings.service';
import { logApiRequest, logError } from '@/lib/logger';
import { writeFile, mkdir, stat } from 'fs/promises';
import { join } from 'path';

const UPLOAD_DIR = join(process.cwd(), 'data');
const UPLOAD_FILENAME = 'neetrino.zip';
const RELATIVE_PATH = 'data/neetrino.zip';

export async function GET() {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const path = await getPluginPackagePath();
    if (!path) {
      return NextResponse.json({
        uploaded: false,
        message: 'ZIP плагина не загружен',
      });
    }

    const absolutePath = join(process.cwd(), path);
    let size = 0;
    try {
      const st = await stat(absolutePath);
      size = st.size;
    } catch {
      return NextResponse.json({
        uploaded: false,
        message: 'Файл не найден на диске',
      });
    }

    logApiRequest('GET', '/api/settings/plugin-package', 200, Date.now() - startTime);
    return NextResponse.json({
      uploaded: true,
      path: RELATIVE_PATH,
      size,
      message: 'При обновлении плагина на сайтах будет использоваться этот ZIP.',
    });
  } catch (error) {
    logError(error as Error, { route: 'GET /api/settings/plugin-package' });
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const formData = await request.formData();
    const file = formData.get('file') as File | null;
    if (!file || file.size === 0) {
      return NextResponse.json({ error: 'Выберите ZIP-файл' }, { status: 400 });
    }

    const name = (file.name || '').toLowerCase();
    if (!name.endsWith('.zip')) {
      return NextResponse.json({ error: 'Допускается только файл .zip' }, { status: 400 });
    }

    await mkdir(UPLOAD_DIR, { recursive: true });
    const bytes = new Uint8Array(await file.arrayBuffer());
    const targetPath = join(UPLOAD_DIR, UPLOAD_FILENAME);
    await writeFile(targetPath, bytes);

    await setPluginPackagePath(RELATIVE_PATH);

    logApiRequest('POST', '/api/settings/plugin-package', 200, Date.now() - startTime, {
      size: bytes.length,
    });
    return NextResponse.json({
      success: true,
      message: 'ZIP плагина загружен. При обновлении плагина на сайтах будет использоваться этот файл.',
      size: bytes.length,
    });
  } catch (error) {
    logError(error as Error, { route: 'POST /api/settings/plugin-package' });
    return NextResponse.json({ error: 'Ошибка загрузки' }, { status: 500 });
  }
}

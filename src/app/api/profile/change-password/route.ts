/**
 * POST /api/profile/change-password — смена пароля
 */

import { NextRequest, NextResponse } from 'next/server';
import { auth } from '@/lib/auth';
import { changePassword } from '@/services/auth.service';
import { changePasswordSchema } from '@/schemas/auth.schema';
import { logApiRequest, logError } from '@/lib/logger';

export async function POST(request: NextRequest) {
  const startTime = Date.now();
  try {
    const session = await auth();
    if (!session?.user?.id) {
      logApiRequest('POST', '/api/profile/change-password', 401, Date.now() - startTime);
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }
    const body = await request.json();
    const parsed = changePasswordSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: 'Invalid input', details: parsed.error.flatten() },
        { status: 400 }
      );
    }
    const { currentPassword, newPassword } = parsed.data;
    const result = await changePassword(
      parseInt(session.user.id, 10),
      currentPassword,
      newPassword
    );
    if (!result.success) {
      return NextResponse.json({ error: result.error }, { status: 400 });
    }
    logApiRequest('POST', '/api/profile/change-password', 200, Date.now() - startTime);
    return NextResponse.json({ success: true });
  } catch (error) {
    logError(error as Error, { route: 'POST /api/profile/change-password' });
    logApiRequest('POST', '/api/profile/change-password', 500, Date.now() - startTime);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}

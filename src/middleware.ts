/**
 * Next.js Middleware
 * - Защита роутов (авторизация)
 * - Редиректы для совместимости с PHP
 */

import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// Публичные пути, не требующие авторизации
const publicPaths = [
  '/login',
  '/api/auth',
  '/api/webhook', // Webhook'и от плагинов
];

// PHP редиректы
const phpRedirects: Record<string, string> = {
  '/index.php': '/',
  '/login.php': '/login',
  '/logout.php': '/api/auth/signout',
  '/profile.php': '/profile',
  '/recycle_bin.php': '/trash',
  '/diagnosis.php': '/diagnosis',
};

export async function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  
  // Редиректы с PHP URL
  if (phpRedirects[pathname]) {
    return NextResponse.redirect(new URL(phpRedirects[pathname], request.url));
  }
  
  // Статические файлы пропускаем
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/favicon') ||
    pathname.startsWith('/icon') ||
    pathname.includes('.')
  ) {
    return NextResponse.next();
  }
  
  // Проверяем публичные пути
  const isPublicPath = publicPaths.some((path) => pathname.startsWith(path));
  if (isPublicPath) {
    return NextResponse.next();
  }
  
  // Для защищённых путей проверка авторизации происходит в layout
  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
};

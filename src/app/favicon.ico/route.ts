import { NextResponse } from 'next/server';

/**
 * Route handler для favicon.ico
 * Возвращает SVG иконку как ICO (для совместимости)
 */
export async function GET() {
  const svgIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
  <rect width="32" height="32" fill="#2563eb" rx="6"/>
  <path d="M16 8 L20 14 L26 16 L20 18 L16 24 L12 18 L6 16 L12 14 Z" fill="white"/>
</svg>`;

  return new NextResponse(svgIcon, {
    headers: {
      'Content-Type': 'image/svg+xml',
      'Cache-Control': 'public, max-age=31536000, immutable',
    },
  });
}

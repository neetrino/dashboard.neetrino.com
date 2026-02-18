/** @type {import('next').NextConfig} */
const nextConfig = {
  // Настройки для production
  reactStrictMode: true,
  
  // Логирование
  logging: {
    fetches: {
      fullUrl: true,
    },
  },
  
  // Перенаправления для совместимости с PHP
  async redirects() {
    return [
      {
        source: '/index.php',
        destination: '/',
        permanent: true,
      },
      {
        source: '/login.php',
        destination: '/login',
        permanent: true,
      },
      {
        source: '/logout.php',
        destination: '/api/auth/signout',
        permanent: true,
      },
      {
        source: '/profile.php',
        destination: '/profile',
        permanent: true,
      },
      {
        source: '/recycle_bin.php',
        destination: '/trash',
        permanent: true,
      },
      {
        source: '/diagnosis.php',
        destination: '/diagnosis',
        permanent: true,
      },
    ];
  },
  
  // Заголовки безопасности
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'X-Frame-Options',
            value: 'DENY',
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff',
          },
          {
            key: 'Referrer-Policy',
            value: 'strict-origin-when-cross-origin',
          },
        ],
      },
    ];
  },
};

module.exports = nextConfig;

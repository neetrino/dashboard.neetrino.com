# Деплой на Vercel

## 1. Подключи репозиторий

- [Vercel](https://vercel.com) → Add New Project → Import Git Repository (GitHub).
- Выбери репозиторий `neetrino-com/dashboard-control.neetrino.com`.

## 2. Переменные окружения

В проекте: **Settings → Environment Variables**. Добавь для **Production** (и при необходимости Preview):

| Name | Value | Обязательно |
|------|--------|-------------|
| `DATABASE_URL` | `mysql://user:password@host:3306/database?connection_limit=5` | Да |
| `NEXTAUTH_URL` | `https://твой-проект.vercel.app` (или свой домен) | Да |
| `NEXTAUTH_SECRET` | Строка **не короче 32 символов** (например `openssl rand -base64 32`) | Да |

Остальные переменные из `.env.example` опциональны.

## 3. База данных

На Vercel нет встроенного MySQL. Нужна внешняя БД:

- **PlanetScale** — MySQL-совместимо, есть бесплатный план.
- **Railway** — MySQL + connection pooler.
- **Aiven**, **DigitalOcean** и др.

Формат: `mysql://USER:PASSWORD@HOST:PORT/DATABASE?connection_limit=5`.  
После деплоя выполни миграции локально, указав этот же `DATABASE_URL`:

```bash
DATABASE_URL="твой_url" npx prisma db push
# или
DATABASE_URL="твой_url" npx prisma migrate deploy
```

## 4. Деплой

После сохранения переменных нажми **Deploy**. Сборка: `npm install` → `prisma generate` (postinstall) → `next build`.

## 5. Домен

В **Settings → Domains** добавь свой домен и обнови `NEXTAUTH_URL` на этот домен.

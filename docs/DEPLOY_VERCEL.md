# Деплой на Vercel

## 1. Подключи репозиторий

- [Vercel](https://vercel.com) → Add New Project → Import Git Repository (GitHub).
- Выбери репозиторий `neetrino-com/dashboard-control.neetrino.com`.

## 2. Переменные окружения

В проекте: **Settings → Environment Variables**. Добавь для **Production** (и при необходимости Preview):

| Name | Value | Обязательно |
|------|--------|-------------|
| `DATABASE_URL` | PostgreSQL URL (Neon: из Dashboard, pooler) | Да |
| `NEXTAUTH_URL` | `https://твой-проект.vercel.app` (или свой домен) | Да |
| `NEXTAUTH_SECRET` | Строка **не короче 32 символов** (например `openssl rand -base64 32`) | Да |

Остальные переменные из `.env.example` опциональны.

## 3. База данных

Используется **PostgreSQL** (Neon и др.). Рекомендуется **Neon** (бесплатный tier, serverless).

- В [Neon Dashboard](https://console.neon.tech) скопируй **connection string** (лучше pooler для serverless).
- Формат: `postgresql://USER:PASSWORD@HOST/neondb?sslmode=require`

После деплоя примени схему к БД локально с тем же `DATABASE_URL`:

```bash
DATABASE_URL="твой_url" npx prisma db push
# или
DATABASE_URL="твой_url" npx prisma migrate deploy
```

## 4. Деплой

После сохранения переменных нажми **Deploy**. Сборка: `npm install` → `prisma generate` (postinstall) → `next build`.

## 5. Домен

В **Settings → Domains** добавь свой домен и обнови `NEXTAUTH_URL` на этот домен.

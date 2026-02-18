# Neetrino Dashboard (Next.js)

Централизованная панель управления WordPress сайтами с плагином Neetrino.

## Технологический стек

- **Framework:** Next.js 14 (App Router)
- **Language:** TypeScript
- **ORM:** Prisma
- **Auth:** NextAuth.js v5
- **UI:** TailwindCSS + shadcn/ui
- **State:** React Query + Zustand
- **Forms:** react-hook-form + Zod

## Быстрый старт

### 1. Установка зависимостей

```bash
npm install
```

### 2. Настройка окружения

Создайте файл `.env.local` на основе `.env.example`:

```bash
cp .env.example .env.local
```

Отредактируйте `.env.local` и укажите:
- `DATABASE_URL` - URL подключения к MySQL
- `NEXTAUTH_SECRET` - секретный ключ для сессий (генерируйте: `openssl rand -base64 32`)

### 3. Генерация Prisma клиента

```bash
npm run db:generate
```

### 4. Запуск в режиме разработки

```bash
npm run dev
```

Откройте [http://localhost:3000](http://localhost:3000)

## Структура проекта

```
src/
├── app/                    # Next.js App Router
│   ├── (auth)/            # Страницы авторизации
│   ├── (dashboard)/       # Защищённые страницы
│   └── api/               # API Routes
├── components/            # React компоненты
│   ├── ui/               # shadcn/ui компоненты
│   ├── layout/           # Layout компоненты
│   └── sites/            # Компоненты для сайтов
├── lib/                   # Утилиты и конфигурация
├── services/              # Бизнес-логика
├── schemas/               # Zod схемы валидации
├── types/                 # TypeScript типы
└── hooks/                 # React хуки
```

## API Endpoints

### Сайты
- `GET /api/sites` - Список сайтов с пагинацией
- `POST /api/sites` - Создание сайта
- `GET /api/sites/[id]` - Получение сайта
- `PATCH /api/sites/[id]` - Обновление сайта
- `DELETE /api/sites/[id]` - Удаление сайта
- `POST /api/sites/[id]/command` - Отправка команды на сайт

### Webhook'и (для плагина)
- `POST /api/webhook/register` - Регистрация сайта
- `POST /api/webhook/version-push` - Push версии плагина

## Совместимость с PHP

Next.js версия работает с той же MySQL базой данных, что и PHP версия.
URL-редиректы настроены для совместимости:

- `/index.php` → `/`
- `/login.php` → `/login`
- `/profile.php` → `/profile`
- `/recycle_bin.php` → `/trash`

## Разработка

### Проверка типов

```bash
npm run type-check
```

### Линтинг

```bash
npm run lint
```

### Тесты

```bash
npm run test        # Unit тесты
npm run test:e2e    # E2E тесты
```

## Деплой

### Vercel (рекомендуется)

```bash
vercel deploy
```

### Docker

```bash
docker build -t neetrino-dashboard .
docker run -p 3000:3000 neetrino-dashboard
```

## Лицензия

Проприетарное ПО. Все права защищены.

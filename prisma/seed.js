/**
 * Seed: создаёт первого админа, если его ещё нет.
 * Запуск: npx prisma db seed
 * Логин: admin@neetrino.com / admin123
 */

require('dotenv').config({ path: '.env.local' });
require('dotenv').config();

const { PrismaClient } = require('@prisma/client');
const bcrypt = require('bcryptjs');

const prisma = new PrismaClient();

const ADMIN_EMAIL = 'admin@neetrino.com';
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin123';

async function main() {
  const existing = await prisma.adminUser.findFirst({
    where: { email: ADMIN_EMAIL },
  });
  if (existing) {
    console.log('Админ уже существует:', ADMIN_EMAIL);
    return;
  }
  const passwordHash = await bcrypt.hash(ADMIN_PASSWORD, 10);
  await prisma.adminUser.create({
    data: {
      username: ADMIN_USERNAME,
      email: ADMIN_EMAIL,
      passwordHash,
    },
  });
  console.log('Создан админ:', ADMIN_EMAIL, '(логин: admin, пароль: admin123)');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());

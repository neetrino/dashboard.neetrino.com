import { auth } from '@/lib/auth';
import { Header } from '@/components/layout/Header';
import { redirect } from 'next/navigation';

/** Не пререндерить статически — auth() использует headers() */
export const dynamic = 'force-dynamic';

/**
 * Layout для защищённых страниц дашборда
 */
export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  try {
    const session = await auth();
    
    // Если не авторизован - редирект на страницу входа
    if (!session?.user) {
      redirect('/login');
    }
    
    return (
      <div className="min-h-screen bg-gray-50">
        <Header user={session.user} />
        <main className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          {children}
        </main>
      </div>
    );
  } catch (error) {
    console.error('Error in dashboard layout:', error);
    // В случае ошибки (например, БД недоступна) редиректим на логин
    redirect('/login');
  }
}

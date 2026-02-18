'use client';

import Link from 'next/link';
import { signOut } from 'next-auth/react';
import { Button } from '@/components/ui/button';
import { User, LogOut, Settings } from 'lucide-react';

interface HeaderProps {
  user: {
    id: string;
    name: string;
    email: string;
    role: string;
  };
}

export function Header({ user }: HeaderProps) {
  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          {/* Логотип и название */}
          <div className="flex items-center">
            <Link href="/" className="flex items-center">
              <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                N
              </div>
              <div className="ml-3">
                <h1 className="text-xl font-semibold text-gray-900">
                  Neetrino Dashboard
                </h1>
                <p className="text-xs text-gray-500">v2.0 Next.js</p>
              </div>
            </Link>
          </div>

          {/* Навигация */}
          <nav className="hidden md:flex items-center space-x-4">
            <Link
              href="/"
              className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              Главная
            </Link>
            <Link
              href="/trash"
              className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              Корзина
            </Link>
            <Link
              href="/settings"
              className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              Настройки
            </Link>
          </nav>

          {/* Профиль */}
          <div className="flex items-center space-x-3">
            <div className="hidden sm:flex items-center text-sm text-gray-600">
              <User className="w-4 h-4 mr-1" />
              <span>{user.name}</span>
            </div>

            <Link href="/profile">
              <Button variant="ghost" size="icon" title="Профиль">
                <Settings className="w-4 h-4" />
              </Button>
            </Link>

            <Button
              variant="ghost"
              size="icon"
              onClick={() => signOut({ callbackUrl: '/login' })}
              title="Выход"
            >
              <LogOut className="w-4 h-4" />
            </Button>
          </div>
        </div>
      </div>
    </header>
  );
}

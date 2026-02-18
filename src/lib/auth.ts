/**
 * NextAuth.js конфигурация
 */

import NextAuth from 'next-auth';
import CredentialsProvider from 'next-auth/providers/credentials';
import { authenticateUser } from '@/services/auth.service';
import { config } from '@/lib/config';

export const { handlers, auth, signIn, signOut } = NextAuth({
  providers: [
    CredentialsProvider({
      name: 'credentials',
      credentials: {
        username: { label: 'Username', type: 'text' },
        password: { label: 'Password', type: 'password' },
      },
      async authorize(credentials, request) {
        if (!credentials?.username || !credentials?.password) {
          return null;
        }
        
        // Получаем IP адрес
        const forwarded = request.headers?.get?.('x-forwarded-for');
        const ip = forwarded?.split(',')[0]?.trim() || '127.0.0.1';
        
        const result = await authenticateUser(
          credentials.username as string,
          credentials.password as string,
          ip
        );
        
        if (!result.success || !result.user) {
          return null;
        }
        
        return {
          id: String(result.user.id),
          name: result.user.username,
          email: result.user.email,
          role: result.user.role,
        };
      },
    }),
  ],
  
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.id = user.id;
        token.role = user.role;
      }
      return token;
    },
    
    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.id as string;
        session.user.role = token.role as string;
      }
      return session;
    },
  },
  
  pages: {
    signIn: '/login',
    error: '/login',
  },
  
  session: {
    strategy: 'jwt',
    maxAge: config.SESSION_MAX_AGE,
  },
  
  secret: config.NEXTAUTH_SECRET,
});

// Расширение типов NextAuth
declare module 'next-auth' {
  interface User {
    role?: string;
  }
  
  interface Session {
    user: {
      id: string;
      name: string;
      email: string;
      role: string;
    };
  }
}

'use client';

import { useQuery } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Stethoscope, RefreshCw, CheckCircle2, XCircle, Loader2 } from 'lucide-react';

async function fetchDiagnosis(): Promise<{
  success: boolean;
  checks: { name: string; status: 'ok' | 'error'; message?: string }[];
  serverTime?: string;
}> {
  const res = await fetch('/api/diagnosis');
  if (!res.ok) throw new Error('Ошибка загрузки');
  return res.json();
}

export default function DiagnosisPage() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['diagnosis'],
    queryFn: fetchDiagnosis,
  });

  return (
    <div className="space-y-6 max-w-xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Диагностика</h1>
          <p className="text-sm text-gray-500 mt-1">Проверка системы и подключений</p>
        </div>
        <Button
          variant="outline"
          size="sm"
          onClick={() => refetch()}
          disabled={isRefetching}
        >
          <RefreshCw className={`w-4 h-4 mr-2 ${isRefetching ? 'animate-spin' : ''}`} />
          Проверить
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Stethoscope className="w-5 h-5" />
            Диагностика системы
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? (
            <div className="flex items-center gap-2 text-gray-500">
              <Loader2 className="w-5 h-5 animate-spin" />
              Загрузка...
            </div>
          ) : data?.checks?.length ? (
            <ul className="space-y-3">
              {data.checks.map((check) => (
                <li
                  key={check.name}
                  className="flex items-center gap-3 text-sm"
                >
                  {check.status === 'ok' ? (
                    <CheckCircle2 className="w-5 h-5 text-green-600 flex-shrink-0" />
                  ) : (
                    <XCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                  )}
                  <span className="font-medium text-gray-700">{check.name}</span>
                  {check.message && (
                    <span className="text-gray-500">— {check.message}</span>
                  )}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-gray-500">Нет данных</p>
          )}
          {data?.serverTime && (
            <p className="text-xs text-gray-400 pt-2 border-t border-gray-100">
              Время сервера: {new Date(data.serverTime).toLocaleString('ru')}
            </p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

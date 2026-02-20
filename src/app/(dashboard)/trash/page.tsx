'use client';

import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { RefreshCw, RotateCcw, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import type { TrashItem } from '@/types';

async function fetchTrash(): Promise<{ items: TrashItem[] }> {
  const res = await fetch('/api/trash');
  if (!res.ok) throw new Error('Ошибка загрузки корзины');
  return res.json();
}

export default function TrashPage() {
  const queryClient = useQueryClient();
  const [restoringId, setRestoringId] = useState<number | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [restoringAll, setRestoringAll] = useState(false);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['trash'],
    queryFn: fetchTrash,
  });

  const items = data?.items ?? [];

  const handleRestore = async (id: number) => {
    setRestoringId(id);
    try {
      const res = await fetch(`/api/trash/${id}/restore`, { method: 'POST' });
      if (!res.ok) {
        const j = await res.json().catch(() => ({}));
        toast.error(j.error || 'Ошибка восстановления');
        return;
      }
      toast.success('Сайт восстановлен');
      await queryClient.invalidateQueries({ queryKey: ['trash'] });
      await queryClient.invalidateQueries({ queryKey: ['sites'] });
    } finally {
      setRestoringId(null);
    }
  };

  const handleRestoreAll = async () => {
    if (items.length === 0) return;
    if (!confirm(`Восстановить все ${items.length} сайтов из корзины?`)) return;
    setRestoringAll(true);
    try {
      const res = await fetch('/api/trash/restore-all', { method: 'POST' });
      const j = await res.json().catch(() => ({}));
      if (!res.ok) {
        toast.error(j.error || 'Ошибка');
        return;
      }
      toast.success(j.message || `Восстановлено: ${j.restored}`);
      await queryClient.invalidateQueries({ queryKey: ['trash'] });
      await queryClient.invalidateQueries({ queryKey: ['sites'] });
    } finally {
      setRestoringAll(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Удалить навсегда? Восстановить будет нельзя.')) return;
    setDeletingId(id);
    try {
      const res = await fetch(`/api/trash/${id}`, { method: 'DELETE' });
      if (!res.ok) throw new Error('Ошибка удаления');
      toast.success('Удалено');
      await queryClient.invalidateQueries({ queryKey: ['trash'] });
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Корзина</h1>
          <p className="text-sm text-gray-500 mt-1">
            Удалённые сайты. Можно восстановить или удалить навсегда.
          </p>
        </div>
        <div className="flex items-center gap-2">
          {items.length > 0 && (
            <Button
              variant="default"
              onClick={handleRestoreAll}
              disabled={restoringAll}
            >
              {restoringAll ? (
                <Loader2 className="w-4 h-4 animate-spin mr-2" />
              ) : (
                <RotateCcw className="w-4 h-4 mr-2" />
              )}
              Восстановить все
            </Button>
          )}
          <Button
            variant="outline"
            onClick={() => refetch()}
            disabled={isRefetching}
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${isRefetching ? 'animate-spin' : ''}`} />
            Обновить
          </Button>
        </div>
      </div>

      {isLoading ? (
        <p className="text-gray-500">Загрузка...</p>
      ) : items.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-gray-500">
            Корзина пуста
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {items.map((item) => (
            <Card key={item.id}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-base">{item.siteName || item.siteUrl}</CardTitle>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleRestore(item.id)}
                    disabled={restoringId !== null}
                  >
                    {restoringId === item.id ? (
                      <RefreshCw className="w-4 h-4 animate-spin" />
                    ) : (
                      <RotateCcw className="w-4 h-4 mr-1" />
                    )}
                    Восстановить
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleDelete(item.id)}
                    disabled={deletingId !== null}
                    className="text-red-600 hover:text-red-700"
                  >
                    {deletingId === item.id ? (
                      <RefreshCw className="w-4 h-4 animate-spin" />
                    ) : (
                      <Trash2 className="w-4 h-4 mr-1" />
                    )}
                    Удалить навсегда
                  </Button>
                </div>
              </CardHeader>
              <CardContent className="text-sm text-gray-600">
                <div>URL: {item.siteUrl}</div>
                <div>
                  Удалён: {new Date(item.deletedAt).toLocaleString('ru')}
                  {item.deletedReason && ` • ${item.deletedReason}`}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}

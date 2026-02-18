'use client';

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import { 
  ExternalLink, 
  Settings, 
  Trash2, 
  RefreshCw,
  Loader2 
} from 'lucide-react';
import { formatRelativeTime } from '@/lib/utils';
import type { SiteWithVersion } from '@/types';
import { toast } from 'sonner';

interface SiteCardProps {
  site: SiteWithVersion;
}

export function SiteCard({ site }: SiteCardProps) {
  const queryClient = useQueryClient();
  const [isDeleting, setIsDeleting] = useState(false);

  const checkStatusMutation = useMutation({
    mutationFn: async () => {
      const res = await fetch(`/api/sites/${site.id}/command`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ command: 'get_status' }),
      });
      if (!res.ok) throw new Error('Failed to check status');
      return res.json();
    },
    onSuccess: () => {
      toast.success('Статус обновлён');
      queryClient.invalidateQueries({ queryKey: ['sites'] });
    },
    onError: () => {
      toast.error('Ошибка проверки статуса');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async () => {
      const res = await fetch(`/api/sites/${site.id}`, {
        method: 'DELETE',
      });
      if (!res.ok) throw new Error('Failed to delete site');
      return res.json();
    },
    onSuccess: () => {
      toast.success('Сайт перемещён в корзину');
      queryClient.invalidateQueries({ queryKey: ['sites'] });
    },
    onError: () => {
      toast.error('Ошибка удаления');
    },
  });

  const handleDelete = async () => {
    if (!confirm(`Удалить сайт "${site.siteName}" из дашборда?`)) {
      return;
    }
    setIsDeleting(true);
    deleteMutation.mutate();
  };

  const statusColor = {
    online: 'bg-green-500',
    offline: 'bg-red-500',
    maintenance: 'bg-yellow-500',
    suspended: 'bg-gray-500',
  }[site.status];

  const statusText = {
    online: 'Онлайн',
    offline: 'Офлайн',
    maintenance: 'Обслуживание',
    suspended: 'Приостановлен',
  }[site.status];

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        {/* Левая часть - информация о сайте */}
        <div className="flex items-center gap-4">
          {/* Статус */}
          <div className="relative">
            <div
              className={`w-3 h-3 rounded-full ${statusColor}`}
              title={statusText}
            />
            {site.status === 'online' && (
              <div
                className={`absolute inset-0 w-3 h-3 rounded-full ${statusColor} animate-ping opacity-75`}
              />
            )}
          </div>

          {/* Название и URL */}
          <div>
            <div className="flex items-center gap-2">
              <h3 className="font-semibold text-gray-900">{site.siteName}</h3>
              {site.siteVersion?.pluginVersion && (
                <span className="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">
                  v{site.siteVersion.pluginVersion}
                </span>
              )}
            </div>
            <a
              href={site.siteUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-1"
            >
              {site.siteUrl}
              <ExternalLink className="w-3 h-3" />
            </a>
          </div>
        </div>

        {/* Правая часть - время и действия */}
        <div className="flex items-center gap-4">
          {/* Последняя активность */}
          <div className="text-right hidden sm:block">
            <div className="text-xs text-gray-400">Последняя проверка</div>
            <div className="text-sm text-gray-600">
              {formatRelativeTime(site.lastSeen)}
            </div>
          </div>

          {/* Действия */}
          <div className="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => checkStatusMutation.mutate()}
              disabled={checkStatusMutation.isPending}
              title="Проверить статус"
            >
              {checkStatusMutation.isPending ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <RefreshCw className="w-4 h-4" />
              )}
            </Button>

            <Button
              variant="ghost"
              size="icon"
              onClick={() => {
                // TODO: Открыть панель управления
                toast.info('Панель управления в разработке');
              }}
              title="Настройки"
            >
              <Settings className="w-4 h-4" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              onClick={handleDelete}
              disabled={isDeleting || deleteMutation.isPending}
              title="Удалить"
              className="text-red-500 hover:text-red-700 hover:bg-red-50"
            >
              {deleteMutation.isPending ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <Trash2 className="w-4 h-4" />
              )}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

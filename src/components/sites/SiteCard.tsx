'use client';

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import {
  ExternalLink,
  Trash2,
  RefreshCw,
  Loader2,
  ChevronDown,
  ChevronUp,
  Package,
  LogOut,
  Trash,
} from 'lucide-react';
import { formatRelativeTime } from '@/lib/utils';
import type { SiteWithVersion } from '@/types';
import { toast } from 'sonner';

interface SiteCardProps {
  site: SiteWithVersion;
  /** Чекбокс для массового выбора (показывать ли) */
  showCheckbox?: boolean;
  selected?: boolean;
  onSelect?: (checked: boolean) => void;
}

export function SiteCard({ site, showCheckbox, selected, onSelect }: SiteCardProps) {
  const queryClient = useQueryClient();
  const [panelOpen, setPanelOpen] = useState(false);
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

  const updatePluginMutation = useMutation({
    mutationFn: async () => {
      const res = await fetch(`/api/sites/${site.id}/command`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ command: 'update_plugin' }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || 'Ошибка обновления');
      }
      return res.json();
    },
    onSuccess: (data) => {
      toast.success(data?.data?.message || 'Плагин обновлён');
      queryClient.invalidateQueries({ queryKey: ['sites'] });
    },
    onError: (e: Error) => {
      toast.error(e.message || 'Ошибка обновления плагина');
    },
  });

  const removeMutation = useMutation({
    mutationFn: async () => {
      const res = await fetch(`/api/sites/${site.id}`, { method: 'DELETE' });
      if (!res.ok) throw new Error('Failed to remove');
      return res.json();
    },
    onSuccess: () => {
      toast.success('Сайт убран из дашборда');
      queryClient.invalidateQueries({ queryKey: ['sites'] });
    },
    onError: () => {
      toast.error('Ошибка');
    },
  });

  const deletePluginMutation = useMutation({
    mutationFn: async () => {
      const res = await fetch(`/api/sites/${site.id}/delete-plugin`, { method: 'POST' });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || 'Ошибка');
      }
      return res.json();
    },
    onSuccess: () => {
      toast.success('Плагин удалён, сайт в корзине');
      queryClient.invalidateQueries({ queryKey: ['sites'] });
    },
    onError: (e: Error) => {
      toast.error(e.message || 'Ошибка удаления плагина');
    },
  });

  const handleRemove = () => {
    if (!confirm(`Убрать "${site.siteName}" из дашборда? Сайт останется, плагин не удаляется.`)) return;
    setIsDeleting(true);
    removeMutation.mutate();
  };

  const handleDeletePlugin = () => {
    if (!confirm(`Удалить плагин Neetrino с сайта "${site.siteName}" и убрать сайт из дашборда?`)) return;
    setIsDeleting(true);
    deletePluginMutation.mutate();
  };

  const versionLabel = site.siteVersion?.pluginVersion
    ? `V ${site.siteVersion.pluginVersion}`
    : 'V —';

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

  const anyPending =
    checkStatusMutation.isPending ||
    updatePluginMutation.isPending ||
    removeMutation.isPending ||
    deletePluginMutation.isPending;

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
      <div className="p-4 flex items-center justify-between gap-4">
        {/* Чекбокс для массового выбора */}
        {showCheckbox && onSelect !== undefined && (
          <label className="flex-shrink-0 cursor-pointer">
            <input
              type="checkbox"
              checked={!!selected}
              onChange={(e) => onSelect(e.target.checked)}
              className="rounded border-gray-300"
            />
          </label>
        )}

        <div className="flex items-center gap-4 min-w-0 flex-1">
          <div
            className={`w-3 h-3 rounded-full flex-shrink-0 ${statusColor}`}
            title={statusText}
          />
          <div className="min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <h3 className="font-semibold text-gray-900">{site.siteName}</h3>
              <span className="text-xs text-gray-500">{versionLabel}</span>
            </div>
            <a
              href={site.siteUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-1 truncate"
            >
              {site.siteUrl}
              <ExternalLink className="w-3 h-3 flex-shrink-0" />
            </a>
          </div>
        </div>

        <div className="flex items-center gap-2 flex-shrink-0">
          <div className="text-right hidden sm:block">
            <div className="text-xs text-gray-400">Проверка</div>
            <div className="text-sm text-gray-600">{formatRelativeTime(site.lastSeen)}</div>
          </div>
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
            size="sm"
            onClick={() => setPanelOpen((o) => !o)}
            className="text-gray-600"
          >
            Пульт управления
            {panelOpen ? <ChevronUp className="w-4 h-4 ml-1" /> : <ChevronDown className="w-4 h-4 ml-1" />}
          </Button>
        </div>
      </div>

      {/* Пульт управления сайтом */}
      {panelOpen && (
        <div className="border-t border-gray-100 bg-gray-50 px-4 py-3 flex flex-wrap items-center gap-2">
          <span className="text-sm text-gray-500 mr-2">Версия: {versionLabel}</span>
          <Button
            variant="outline"
            size="sm"
            onClick={() => updatePluginMutation.mutate()}
            disabled={!site.apiKey || updatePluginMutation.isPending}
          >
            {updatePluginMutation.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin mr-1" />
            ) : (
              <Package className="w-4 h-4 mr-1" />
            )}
            Обновить плагин
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleRemove}
            disabled={anyPending}
            title="Убрать только из дашборда, плагин на сайте остаётся"
          >
            <LogOut className="w-4 h-4 mr-1" />
            Убрать из дашборда
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={handleDeletePlugin}
            disabled={anyPending}
            className="text-red-600 hover:text-red-700 hover:bg-red-50"
            title="Удалить плагин на сайте и убрать из дашборда"
          >
            <Trash className="w-4 h-4 mr-1" />
            Удалить плагин с сайта
          </Button>
        </div>
      )}
    </div>
  );
}

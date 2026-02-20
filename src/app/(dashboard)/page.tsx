'use client';

import { useState, useCallback } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { SitesList } from '@/components/sites/SitesList';
import { SiteFilters } from '@/components/sites/SiteFilters';
import { SiteStats } from '@/components/sites/SiteStats';
import { AddSiteModal } from '@/components/sites/AddSiteModal';
import { Button } from '@/components/ui/button';
import { RefreshCw, Plus } from 'lucide-react';
import { toast } from 'sonner';
import type { SiteFilters as SiteFiltersType, PaginatedResponse, SiteWithVersion } from '@/types';

const BULK_UPDATE_DELAY_MS = 2500;

async function fetchSites(filters: SiteFiltersType): Promise<PaginatedResponse<SiteWithVersion>> {
  const params = new URLSearchParams();
  if (filters.page) params.set('page', String(filters.page));
  if (filters.perPage) params.set('perPage', String(filters.perPage));
  if (filters.search) params.set('search', filters.search);
  if (filters.status && filters.status !== 'all') params.set('status', filters.status);

  const res = await fetch(`/api/sites?${params}`);
  if (!res.ok) throw new Error('Ошибка загрузки сайтов');
  return res.json();
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export default function DashboardPage() {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<SiteFiltersType>({
    page: 1,
    perPage: 20,
    status: 'all',
  });
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [bulkUpdateProgress, setBulkUpdateProgress] = useState<{ current: number; total: number } | null>(null);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['sites', filters],
    queryFn: () => fetchSites(filters),
    refetchInterval: 30000,
  });

  const handleFiltersChange = (newFilters: Partial<SiteFiltersType>) => {
    setFilters((prev) => ({
      ...prev,
      ...newFilters,
      page: newFilters.search !== undefined || newFilters.status !== undefined ? 1 : (newFilters.page || prev.page),
    }));
  };

  const handlePageChange = (page: number) => {
    setFilters((prev) => ({ ...prev, page }));
  };

  const handleRefresh = () => {
    refetch();
  };

  const handleSelect = useCallback((siteId: number, checked: boolean) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (checked) next.add(siteId);
      else next.delete(siteId);
      return next;
    });
  }, []);

  const handleBulkUpdatePlugin = useCallback(async () => {
    const items = data?.items ?? [];
    const ids = Array.from(selectedIds).filter((id) => items.some((s) => s.id === id));
    if (ids.length === 0) return;

    setBulkUpdateProgress({ current: 0, total: ids.length });
    let done = 0;
    const total = ids.length;

    for (let i = 0; i < ids.length; i++) {
      const id = ids[i];
      try {
        const res = await fetch(`/api/sites/${id}/command`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ command: 'update_plugin' }),
        });
        const json = await res.json().catch(() => ({}));
        if (res.ok && json.success) {
          toast.success(`Сайт ${i + 1}/${total}: обновлён`);
        } else {
          toast.error(`Сайт ${i + 1}/${total}: ${json.error || 'Ошибка'}`);
        }
      } catch (e) {
        toast.error(`Сайт ${i + 1}/${total}: ${e instanceof Error ? e.message : 'Ошибка'}`);
      }
      done = i + 1;
      setBulkUpdateProgress({ current: done, total });
      if (i < ids.length - 1) await delay(BULK_UPDATE_DELAY_MS);
    }

    setBulkUpdateProgress(null);
    setSelectedIds(new Set());
    queryClient.invalidateQueries({ queryKey: ['sites'] });
    toast.success(`Готово: обновлено ${done} из ${total}`);
  }, [data?.items, selectedIds, queryClient]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Управление сайтами</h1>
          <p className="text-sm text-gray-500 mt-1">
            Мониторинг и управление WordPress сайтами
          </p>
        </div>

        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            onClick={handleRefresh}
            disabled={isRefetching}
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${isRefetching ? 'animate-spin' : ''}`} />
            Обновить
          </Button>

          <Button onClick={() => setIsAddModalOpen(true)}>
            <Plus className="w-4 h-4 mr-2" />
            Добавить сайт
          </Button>
        </div>
      </div>

      <SiteStats />

      <SiteFilters
        filters={filters}
        onChange={handleFiltersChange}
        totalCount={data?.pagination.totalItems || 0}
      />

      <SitesList
        sites={data?.items || []}
        pagination={data?.pagination}
        isLoading={isLoading}
        onPageChange={handlePageChange}
        onRefresh={handleRefresh}
        selectedIds={selectedIds}
        onSelect={handleSelect}
        onBulkUpdatePlugin={handleBulkUpdatePlugin}
        bulkUpdateProgress={bulkUpdateProgress}
      />

      <AddSiteModal
        open={isAddModalOpen}
        onOpenChange={setIsAddModalOpen}
        onSuccess={() => {
          setIsAddModalOpen(false);
          refetch();
        }}
      />
    </div>
  );
}

'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { SitesList } from '@/components/sites/SitesList';
import { SiteFilters } from '@/components/sites/SiteFilters';
import { SiteStats } from '@/components/sites/SiteStats';
import { AddSiteModal } from '@/components/sites/AddSiteModal';
import { Button } from '@/components/ui/button';
import { RefreshCw, Plus } from 'lucide-react';
import type { SiteFilters as SiteFiltersType, PaginatedResponse, SiteWithVersion } from '@/types';

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

export default function DashboardPage() {
  const [filters, setFilters] = useState<SiteFiltersType>({
    page: 1,
    perPage: 20,
    status: 'all',
  });
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['sites', filters],
    queryFn: () => fetchSites(filters),
    refetchInterval: 30000, // Автообновление каждые 30 секунд
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
  
  return (
    <div className="space-y-6">
      {/* Заголовок и действия */}
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
      
      {/* Статистика */}
      <SiteStats />
      
      {/* Фильтры */}
      <SiteFilters
        filters={filters}
        onChange={handleFiltersChange}
        totalCount={data?.pagination.totalItems || 0}
      />
      
      {/* Список сайтов */}
      <SitesList
        sites={data?.items || []}
        pagination={data?.pagination}
        isLoading={isLoading}
        onPageChange={handlePageChange}
        onRefresh={handleRefresh}
      />
      
      {/* Модальное окно добавления сайта */}
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

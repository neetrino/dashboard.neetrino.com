'use client';

import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search } from 'lucide-react';
import type { SiteFilters as SiteFiltersType, SiteStatus } from '@/types';

interface SiteFiltersProps {
  filters: SiteFiltersType;
  onChange: (filters: Partial<SiteFiltersType>) => void;
  totalCount: number;
}

export function SiteFilters({ filters, onChange, totalCount }: SiteFiltersProps) {
  const statusOptions: { value: SiteStatus | 'all'; label: string; color: string }[] = [
    { value: 'all', label: 'Все', color: 'bg-gray-500' },
    { value: 'online', label: 'Онлайн', color: 'bg-green-500' },
    { value: 'offline', label: 'Офлайн', color: 'bg-red-500' },
    { value: 'maintenance', label: 'Обслуживание', color: 'bg-yellow-500' },
  ];

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
        {/* Поиск */}
        <div className="relative w-full sm:w-auto sm:min-w-[300px]">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <Input
            type="text"
            placeholder="Поиск сайтов..."
            value={filters.search || ''}
            onChange={(e) => onChange({ search: e.target.value })}
            className="pl-10"
          />
        </div>

        {/* Фильтры по статусу */}
        <div className="flex items-center gap-2">
          {statusOptions.map((option) => (
            <Button
              key={option.value}
              variant={filters.status === option.value ? 'default' : 'outline'}
              size="sm"
              onClick={() => onChange({ status: option.value })}
              className="flex items-center gap-2"
            >
              <div className={`w-2 h-2 rounded-full ${option.color}`} />
              {option.label}
            </Button>
          ))}
        </div>

        {/* Счётчик */}
        <div className="text-sm text-gray-500">
          Найдено: <span className="font-semibold">{totalCount}</span>
        </div>
      </div>
    </div>
  );
}

'use client';

import { SiteCard } from './SiteCard';
import { Pagination } from '@/types';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-react';
import type { SiteWithVersion } from '@/types';

interface SitesListProps {
  sites: SiteWithVersion[];
  pagination?: Pagination;
  isLoading: boolean;
  onPageChange: (page: number) => void;
  onRefresh: () => void;
}

export function SitesList({
  sites,
  pagination,
  isLoading,
  onPageChange,
}: SitesListProps) {
  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <Loader2 className="w-8 h-8 animate-spin mx-auto mb-4 text-blue-600" />
          <p className="text-gray-500 text-sm">Загрузка сайтов...</p>
        </div>
      </div>
    );
  }

  if (sites.length === 0) {
    return (
      <div className="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
        <div className="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
          <span className="text-2xl">🌐</span>
        </div>
        <h3 className="text-lg font-medium text-gray-900 mb-2">Нет сайтов</h3>
        <p className="text-gray-500 mb-6">
          Добавьте первый сайт для мониторинга
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Список сайтов */}
      <div className="grid gap-4">
        {sites.map((site) => (
          <SiteCard key={site.id} site={site} />
        ))}
      </div>

      {/* Пагинация */}
      {pagination && pagination.totalPages > 1 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
            <div className="text-sm text-gray-600">
              Страница {pagination.currentPage} из {pagination.totalPages}
              <span className="text-gray-400 mx-2">•</span>
              Всего: {pagination.totalItems} сайтов
            </div>

            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange(pagination.currentPage - 1)}
                disabled={!pagination.hasPrev}
              >
                <ChevronLeft className="w-4 h-4 mr-1" />
                Назад
              </Button>

              {/* Номера страниц */}
              <div className="flex items-center gap-1">
                {Array.from({ length: Math.min(5, pagination.totalPages) }).map(
                  (_, i) => {
                    const pageNum = getPageNumber(
                      i,
                      pagination.currentPage,
                      pagination.totalPages
                    );
                    return (
                      <Button
                        key={pageNum}
                        variant={
                          pageNum === pagination.currentPage
                            ? 'default'
                            : 'outline'
                        }
                        size="sm"
                        className="w-8 h-8 p-0"
                        onClick={() => onPageChange(pageNum)}
                      >
                        {pageNum}
                      </Button>
                    );
                  }
                )}
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange(pagination.currentPage + 1)}
                disabled={!pagination.hasNext}
              >
                Вперёд
                <ChevronRight className="w-4 h-4 ml-1" />
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function getPageNumber(
  index: number,
  currentPage: number,
  totalPages: number
): number {
  if (totalPages <= 5) {
    return index + 1;
  }

  const start = Math.max(1, Math.min(currentPage - 2, totalPages - 4));
  return start + index;
}

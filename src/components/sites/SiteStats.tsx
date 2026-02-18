'use client';

import { useQuery } from '@tanstack/react-query';
import { Globe, CheckCircle, XCircle, Wrench } from 'lucide-react';

async function fetchStats() {
  // Для простоты используем данные из списка сайтов
  // В реальном приложении можно создать отдельный endpoint
  const res = await fetch('/api/sites?perPage=1000');
  if (!res.ok) throw new Error('Failed to fetch stats');
  const data = await res.json();
  
  const sites = data.items || [];
  return {
    total: sites.length,
    online: sites.filter((s: { status: string }) => s.status === 'online').length,
    offline: sites.filter((s: { status: string }) => s.status === 'offline').length,
    maintenance: sites.filter((s: { status: string }) => s.status === 'maintenance').length,
  };
}

export function SiteStats() {
  const { data: stats } = useQuery({
    queryKey: ['siteStats'],
    queryFn: fetchStats,
    refetchInterval: 30000,
  });

  const statItems = [
    {
      label: 'Всего сайтов',
      value: stats?.total || 0,
      icon: Globe,
      color: 'text-blue-600',
      bgColor: 'bg-blue-100',
    },
    {
      label: 'Онлайн',
      value: stats?.online || 0,
      icon: CheckCircle,
      color: 'text-green-600',
      bgColor: 'bg-green-100',
    },
    {
      label: 'Офлайн',
      value: stats?.offline || 0,
      icon: XCircle,
      color: 'text-red-600',
      bgColor: 'bg-red-100',
    },
    {
      label: 'Обслуживание',
      value: stats?.maintenance || 0,
      icon: Wrench,
      color: 'text-yellow-600',
      bgColor: 'bg-yellow-100',
    },
  ];

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      {statItems.map((item) => (
        <div
          key={item.label}
          className="bg-white rounded-xl shadow-sm border border-gray-200 p-4"
        >
          <div className="flex items-center gap-3">
            <div className={`p-2 rounded-lg ${item.bgColor}`}>
              <item.icon className={`w-5 h-5 ${item.color}`} />
            </div>
            <div>
              <div className="text-2xl font-bold text-gray-900">
                {item.value}
              </div>
              <div className="text-sm text-gray-500">{item.label}</div>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

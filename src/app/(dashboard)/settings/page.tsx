'use client';

import { useState, useRef } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Package, Upload, Loader2, CheckCircle2 } from 'lucide-react';
import { toast } from 'sonner';

async function fetchPluginPackageStatus(): Promise<{
  uploaded: boolean;
  path?: string;
  size?: number;
  message?: string;
}> {
  const res = await fetch('/api/settings/plugin-package');
  if (!res.ok) throw new Error('Ошибка загрузки');
  return res.json();
}

export default function SettingsPage() {
  const queryClient = useQueryClient();
  const [uploading, setUploading] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { data: status, isLoading } = useQuery({
    queryKey: ['settings', 'plugin-package'],
    queryFn: fetchPluginPackageStatus,
  });

  const uploadFile = async (file: File) => {
    if (!file.name.toLowerCase().endsWith('.zip')) {
      toast.error('Нужен файл .zip');
      return;
    }
    setUploading(true);
    try {
      const formData = new FormData();
      formData.set('file', file);
      const res = await fetch('/api/settings/plugin-package', {
        method: 'POST',
        body: formData,
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        toast.error(json.error || 'Ошибка загрузки');
        return;
      }
      toast.success(json.message || 'ZIP загружен');
      queryClient.invalidateQueries({ queryKey: ['settings', 'plugin-package'] });
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) uploadFile(file);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
    if (uploading) return;
    const file = e.dataTransfer.files?.[0];
    if (file) uploadFile(file);
  };

  const handleZoneClick = () => {
    if (!uploading) fileInputRef.current?.click();
  };

  return (
    <div className="space-y-6 max-w-xl">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Настройки</h1>
        <p className="text-sm text-gray-500 mt-1">Общие настройки дашборда</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Package className="w-5 h-5" />
            ZIP плагина для обновления
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-gray-600">
            Загрузите ZIP-архив плагина Neetrino. При нажатии «Обновить плагин» на сайте или массовом обновлении будет использоваться этот файл.
          </p>

          {isLoading ? (
            <div className="flex items-center gap-2 text-gray-500 text-sm">
              <Loader2 className="w-4 h-4 animate-spin" />
              Загрузка...
            </div>
          ) : status?.uploaded ? (
            <div className="flex items-center gap-2 text-green-700 text-sm">
              <CheckCircle2 className="w-5 h-5 flex-shrink-0" />
              <span>
                Файл загружен
                {status.size != null && (
                  <span className="text-gray-500 ml-1">
                    ({(status.size / 1024).toFixed(1)} КБ)
                  </span>
                )}
              </span>
            </div>
          ) : (
            <p className="text-sm text-gray-500">{status?.message ?? 'ZIP не загружен'}</p>
          )}

          <input
            ref={fileInputRef}
            type="file"
            accept=".zip"
            className="sr-only"
            onChange={handleInputChange}
          />

          <div
            role="button"
            tabIndex={0}
            onClick={handleZoneClick}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleZoneClick();
              }
            }}
            className={`
              border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors
              min-h-[140px] flex flex-col items-center justify-center gap-2
              ${isDragging
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-300 bg-gray-50/50 hover:border-gray-400 hover:bg-gray-50'}
              ${uploading ? 'pointer-events-none opacity-80' : ''}
            `}
          >
            {uploading ? (
              <>
                <Loader2 className="w-10 h-10 text-blue-500 animate-spin" />
                <span className="text-sm text-gray-600">Загрузка...</span>
              </>
            ) : (
              <>
                <Upload className="w-10 h-10 text-gray-400" />
                <span className="text-sm font-medium text-gray-700">
                  Перетащите ZIP сюда или нажмите для выбора
                </span>
                <span className="text-xs text-gray-500">Только файлы .zip</span>
              </>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { createSiteSchema, type CreateSiteInput } from '@/schemas/site.schema';
import { Loader2, Globe } from 'lucide-react';
import { toast } from 'sonner';

interface AddSiteModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSuccess: () => void;
}

export function AddSiteModal({ open, onOpenChange, onSuccess }: AddSiteModalProps) {
  const [error, setError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<CreateSiteInput>({
    resolver: zodResolver(createSiteSchema),
  });

  const mutation = useMutation({
    mutationFn: async (data: CreateSiteInput) => {
      const res = await fetch('/api/sites', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      
      const result = await res.json();
      
      if (!res.ok) {
        throw new Error(result.error || 'Ошибка добавления сайта');
      }
      
      return result;
    },
    onSuccess: () => {
      toast.success('Сайт добавлен успешно');
      reset();
      setError(null);
      onSuccess();
    },
    onError: (err: Error) => {
      setError(err.message);
    },
  });

  const onSubmit = (data: CreateSiteInput) => {
    setError(null);
    mutation.mutate(data);
  };

  const handleClose = () => {
    reset();
    setError(null);
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Globe className="w-5 h-5 text-blue-600" />
            Добавить новый сайт
          </DialogTitle>
          <DialogDescription>
            Введите данные сайта для добавления в мониторинг
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {error && (
            <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
              ❌ {error}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="siteUrl">URL сайта</Label>
            <Input
              id="siteUrl"
              type="url"
              placeholder="https://example.com"
              {...register('siteUrl')}
            />
            {errors.siteUrl && (
              <p className="text-sm text-red-500">{errors.siteUrl.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="siteName">Название сайта</Label>
            <Input
              id="siteName"
              type="text"
              placeholder="Мой сайт"
              {...register('siteName')}
            />
            {errors.siteName && (
              <p className="text-sm text-red-500">{errors.siteName.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="adminEmail">Email администратора (необязательно)</Label>
            <Input
              id="adminEmail"
              type="email"
              placeholder="admin@example.com"
              {...register('adminEmail')}
            />
            {errors.adminEmail && (
              <p className="text-sm text-red-500">{errors.adminEmail.message}</p>
            )}
          </div>

          <DialogFooter className="gap-2">
            <Button type="button" variant="outline" onClick={handleClose}>
              Отмена
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Добавление...
                </>
              ) : (
                'Добавить сайт'
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

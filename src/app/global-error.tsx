'use client';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <html>
      <body>
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
          <div className="max-w-md w-full space-y-6 text-center">
            <h1 className="text-2xl font-bold text-gray-900">
              Критическая ошибка
            </h1>
            <p className="text-gray-600">
              Произошла критическая ошибка приложения
            </p>
            <button
              onClick={reset}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Перезагрузить
            </button>
          </div>
        </div>
      </body>
    </html>
  );
}

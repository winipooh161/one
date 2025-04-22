<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class TinyMCEController extends Controller
{
    /**
     * Загрузка изображений для TinyMCE и CKEditor
     */
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Файл не загружен'], 400);
        }

        $file = $request->file('file');

        // Проверяем, является ли файл изображением
        if (!$file->isValid() || !Str::startsWith($file->getMimeType(), 'image/')) {
            return response()->json(['error' => 'Недопустимый формат файла'], 400);
        }

        try {
            // Генерируем уникальное имя файла
            $filename = 'content_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Путь для сохранения изображения
            $path = 'content';
            
            // Проверяем и создаем директорию если она не существует
            $uploadPath = public_path('uploads/' . $path);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Сохраняем оригинальное изображение в public/uploads/content
            $img = Image::make($file->getRealPath());
            
            // Опционально: можно ограничить размер изображения
            if ($img->width() > 1200) {
                $img->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            $img->save(public_path('uploads/' . $path . '/' . $filename));
            
            // Формируем URL изображения для возврата
            $imageUrl = asset('uploads/' . $path . '/' . $filename);
            
            // Возвращаем URL в формате, ожидаемом CKEditor
            return response()->json([
                'url' => $imageUrl,
                'location' => $imageUrl // альтернативный формат для некоторых редакторов
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка загрузки: ' . $e->getMessage()], 500);
        }
    }
}

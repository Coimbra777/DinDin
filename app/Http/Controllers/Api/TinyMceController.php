<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TinyMceController extends Controller
{
    private const DISK = 'public';

    private const DIRECTORY = 'TinyMce';

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120', 'mimes:jpeg,png,gif,webp'],
        ]);

        $file = $request->file('file');
        $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs(self::DIRECTORY, $name, self::DISK);

        return response()->json([
            'location' => asset('storage/'.$path),
        ]);
    }

    /**
     * Remove ficheiro apenas sob storage/public/TinyMce/ (nunca path vindo cru do cliente).
     */
    public function removeImage(Request $request): JsonResponse
    {
        $request->validate([
            'location' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'string', 'max:2048'],
        ]);

        $raw = $request->input('location') ?? $request->input('image');
        if ($raw === null || $raw === '') {
            return response()->json(['message' => 'Indique a URL ou caminho da imagem.'], 422);
        }

        $path = parse_url((string) $raw, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return response()->json(['message' => 'URL inválida.'], 422);
        }

        if (! str_starts_with($path, '/storage/')) {
            return response()->json(['message' => 'Caminho não permitido.'], 403);
        }

        $relative = substr($path, strlen('/storage/'));
        if ($relative === '' || str_contains($relative, '..') || ! str_starts_with($relative, self::DIRECTORY.'/')) {
            return response()->json(['message' => 'Só é possível remover imagens enviadas pelo editor (pasta TinyMce).'], 403);
        }

        if (Storage::disk(self::DISK)->exists($relative)) {
            Storage::disk(self::DISK)->delete($relative);

            return response()->json(['message' => 'Mídia removida', 'msg' => 'midia removida']);
        }

        return response()->json(['message' => 'Ficheiro não encontrado.'], 404);
    }
}

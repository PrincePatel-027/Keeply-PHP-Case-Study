<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('view', $item);

        $file = $request->file('document');
        $disk = config('filesystems.default');
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $sha256 = hash_file('sha256', $file->getRealPath());
        $path = $file->store("documents/{$item->workspace_id}", $disk);

        if ($path === false) {
            throw new RuntimeException('The document could not be stored.');
        }

        try {
            DB::transaction(function () use ($request, $item, $file, $disk, $path, $mimeType, $sha256): void {
                $document = $item->workspace->documents()->create([
                    'uploaded_by' => $request->user()->id,
                    'type' => $request->validated('purpose'),
                    'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'mime_type' => $mimeType,
                    'size_bytes' => $file->getSize(),
                    'sha256' => $sha256,
                ]);

                $item->documents()->attach($document, [
                    'purpose' => $request->validated('purpose'),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        return redirect()->route('items.show', $item)->with('status', 'Private document attached.');
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        abort_unless(Storage::disk($document->storage_disk)->exists($document->storage_path), 404);

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}

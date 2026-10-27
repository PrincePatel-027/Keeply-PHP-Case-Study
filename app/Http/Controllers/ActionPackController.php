<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewActionPackRequest;
use App\Models\ActionPack;
use App\Models\ResolutionCase;
use App\Services\ActionPackBuilder;
use App\Services\ActionPackPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActionPackController extends Controller
{
    public function create(
        ResolutionCase $resolutionCase,
        ActionPackBuilder $builder,
    ): View|RedirectResponse {
        Gate::authorize('view', $resolutionCase);
        $resolutionCase->load(['item.category', 'item.documents', 'assessments']);
        $assessment = $resolutionCase->assessments->first();

        if ($assessment === null) {
            return redirect()
                ->route('cases.show', $resolutionCase)
                ->withErrors(['action_pack' => 'Save an assessment snapshot before creating an Action Pack.']);
        }

        $defaultLetter = $builder->defaultLetter($resolutionCase, $assessment, request()->user());

        return view('action-packs.create', [
            'resolutionCase' => $resolutionCase,
            'assessments' => $resolutionCase->assessments,
            'documents' => $resolutionCase->item->documents,
            'defaultLetter' => $defaultLetter,
        ]);
    }

    public function preview(
        PreviewActionPackRequest $request,
        ResolutionCase $resolutionCase,
        ActionPackBuilder $builder,
    ): View {
        Gate::authorize('view', $resolutionCase);
        $assessment = $resolutionCase->assessments()
            ->findOrFail($request->integer('assessment_id'));
        $documents = $resolutionCase->item->documents()
            ->whereIn('documents.id', $request->input('document_ids', []))
            ->get();
        $pack = $builder->build(
            $resolutionCase,
            $assessment,
            $documents,
            $request->validated('letter_body'),
            $request->user(),
        );

        return view('action-packs.preview', compact('pack'));
    }

    public function store(
        PreviewActionPackRequest $request,
        ResolutionCase $resolutionCase,
        ActionPackBuilder $builder,
        ActionPackPdfService $pdfService,
    ): RedirectResponse {
        Gate::authorize('view', $resolutionCase);
        $assessment = $resolutionCase->assessments()
            ->findOrFail($request->integer('assessment_id'));
        $documents = $resolutionCase->item->documents()
            ->whereIn('documents.id', $request->input('document_ids', []))
            ->get();
        $pack = $builder->build(
            $resolutionCase,
            $assessment,
            $documents,
            $request->validated('letter_body'),
            $request->user(),
        );
        $actionPack = $pdfService->create($pack);

        return redirect()
            ->route('cases.show', $resolutionCase)
            ->with('status', "Action Pack v{$actionPack->version} generated privately.");
    }

    public function download(ActionPack $actionPack): StreamedResponse
    {
        Gate::authorize('view', $actionPack);
        $actionPack->load('pdfDocument');
        $document = $actionPack->pdfDocument;

        abort_if($document === null, 404);
        abort_unless(Storage::disk($document->storage_disk)->exists($document->storage_path), 404);

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            $document->original_name,
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}

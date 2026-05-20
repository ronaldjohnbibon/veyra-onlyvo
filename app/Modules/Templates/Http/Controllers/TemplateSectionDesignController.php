<?php

namespace App\Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Templates\Http\Requests\TemplateSectionDesignRequest;
use App\Modules\Templates\Http\Resources\TemplateSectionDesignResource;
use App\Modules\Templates\Models\TemplateSectionDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateSectionDesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sorts = [
            'default_sort_order' => 'default_sort_order',
            'design_key'         => 'design_key',
            'is_active'          => 'is_active',
            'name'               => 'name',
            'section_type'       => 'section_type',
        ];
        $sort       = (string) $request->input('sort', 'default_sort_order');
        $sortColumn = $sorts[$sort] ?? 'default_sort_order';
        $direction  = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $designs = TemplateSectionDesign::query()
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('section_type', 'like', "%{$search}%")
                        ->orWhere('design_key', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortColumn, $direction)
            ->orderBy('section_type')
            ->orderBy('name')
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(TemplateSectionDesignResource::collection($designs), 'Template designs retrieved.');
    }

    public function store(TemplateSectionDesignRequest $request): JsonResponse
    {
        $design = TemplateSectionDesign::query()->create($request->validated());

        return $this->success(new TemplateSectionDesignResource($design), 'Template design saved.', 201);
    }

    public function show(string $templateSectionDesign): JsonResponse
    {
        $design = TemplateSectionDesign::query()->find($templateSectionDesign);

        if (! $design) {
            return $this->error('Template design not found.', 404);
        }

        return $this->success(new TemplateSectionDesignResource($design), 'Template design retrieved.');
    }

    public function update(TemplateSectionDesignRequest $request, string $templateSectionDesign): JsonResponse
    {
        $design = TemplateSectionDesign::query()->find($templateSectionDesign);

        if (! $design) {
            return $this->error('Template design not found.', 404);
        }

        $design->update($request->validated());

        return $this->success(new TemplateSectionDesignResource($design->fresh()), 'Template design saved.');
    }

    public function destroy(string $templateSectionDesign): JsonResponse
    {
        $design = TemplateSectionDesign::query()->find($templateSectionDesign);

        if (! $design) {
            return $this->error('Template design not found.', 404);
        }

        $design->delete();

        return $this->success(null, 'Template design deleted.');
    }
}

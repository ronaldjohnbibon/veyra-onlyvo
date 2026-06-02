<?php

namespace App\Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Templates\Http\Resources\TemplateResource;
use App\Modules\Templates\Models\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PublicTemplateController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $template = $this->publishedQuery()
            ->where('slug', $slug)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    public function defaultSite(): JsonResponse
    {
        $template = $this->publishedQuery()
            ->where('is_default', true)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    private function publishedQuery(): Builder
    {
        return Template::query()
            ->with('websiteType')
            ->where('status', 'published');
    }
}

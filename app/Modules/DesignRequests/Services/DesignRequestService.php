<?php

namespace App\Modules\DesignRequests\Services;

use App\Modules\DesignRequests\Models\DesignRequest;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DesignRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): DesignRequest
    {
        return DB::transaction(function () use ($user, $data): DesignRequest {
            $files = $data['files'] ?? [];
            unset($data['files']);

            // Store the request before attaching uploaded mockup files.
            $request = DesignRequest::query()->create(array_merge($data, [
                'tenant_id' => (string) $user->tenant_id,
                'user_id'   => $user->id,
                'status'    => 'pending',
            ]));

            $this->storeFiles($request, is_array($files) ? $files : []);

            return $request->fresh(['files', 'tenant', 'requester']);
        });
    }

    /**
     * @param  array<int, mixed>  $files
     */
    private function storeFiles(DesignRequest $request, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->storePublicly(
                "design-requests/{$request->tenant_id}/{$request->id}",
                'public',
            );

            $request->files()->create([
                'name'      => $file->getClientOriginalName(),
                'path'      => $path,
                'mime_type' => $file->getClientMimeType(),
                'size'      => $file->getSize() ?: 0,
            ]);
        }
    }
}

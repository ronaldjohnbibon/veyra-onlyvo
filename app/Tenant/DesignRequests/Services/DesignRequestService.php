<?php

namespace App\Tenant\DesignRequests\Services;

use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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
     * @param  array<string, mixed>  $data
     */
    public function review(DesignRequest $request, array $data, int|string|null $reviewerId): DesignRequest
    {
        $status = (string) $data['status'];
        $now    = Carbon::now();

        $request->update([
            'status'        => $status,
            'admin_remarks' => $data['admin_remarks'] ?? null,
            'reviewed_by'   => $reviewerId,
            'reviewed_at'   => $now,
            'completed_at'  => $status === 'completed' ? $now : null,
        ]);

        return $request;
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

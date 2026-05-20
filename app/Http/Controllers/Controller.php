<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    protected function success(mixed $data = [], ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status'  => true,
            'message' => $message ?? 'Request processed successfully.',
        ];

        if (! $data instanceof AnonymousResourceCollection) {
            $response['data'] = $data;

            return response()->json($response, $status);
        }

        $response['data'] = $data->collection;

        // Include pagination only when the resource wraps a paginator.
        if ($data->resource instanceof LengthAwarePaginator) {
            $paginator = $data->resource;

            $response['pagination'] = [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ];
        }

        return response()->json($response, $status);
    }

    protected function error(?string $message = null, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message ?? 'Request failed.',
            'errors'  => $errors,
        ], $status);
    }
}

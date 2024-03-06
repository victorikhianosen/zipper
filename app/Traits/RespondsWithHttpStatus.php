<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

trait RespondsWithHttpStatus
{
    /**
     * @param  Collection|JsonResource|array<mixed, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function success(
        ?string $message = 'Request successful.',
        Collection|JsonResource|array $data = [],
        array $meta = [],
        int $code = 200
    ): JsonResponse {
        $res['message'] = $message;

        if ($data) {
            $res['data'] = $data;
        }
        if ($meta) {
            $res['meta'] = $meta;
        }

        return response()->json($res, $code);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public function failure(
        ?string $message = 'Request failed.',
        array $errors = [],
        int $code = 400
    ): JsonResponse {
        $res['message'] = $message;
        if ($errors) {
            $res['errors'] = $errors;
        }

        return response()->json($res, $code);
    }
}

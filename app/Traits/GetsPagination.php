<?php

namespace App\Traits;

trait GetsPagination
{
    /**
     * @param  array<string,mixed>  $colums
     * @return array<string, mixed>
     */
    public function paginate(mixed $query, array $columns = ['*']): array // @phpstan-ignore-line
    {
        $page = (int) (request()->page ?? 1);
        $perPage = (int) (request()->per_page ?? 10);
        $total = $query->count();

        return [
            'data' => $query->offset($perPage * ($page - 1))->limit($perPage)->get($columns),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * @param  array<mixed, mixed>  $columns
     */
    public static function withRelationAndColumns(string $relation, array $columns = []): Builder
    {
        if (blank($columns)) {
            $data = self::with([$relation]);
        } else {
            $columns = ['id', ...$columns];
            $data = self::with([$relation => function ($query) use ($columns) {
                $query->select($columns);
            }]);
        }

        return $data;
    }
}

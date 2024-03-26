<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected $table = 'faq_categories';

    protected $hidden = ['created_at', 'updated_at'];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}

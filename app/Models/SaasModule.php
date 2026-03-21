<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catálogo de módulos para permissão por utilizador (SaaS / entitlements).
 * Distinto de {@see Module} (menu CMS / group_module).
 */
class SaasModule extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saas_module_user')
            ->withTimestamps();
    }
}

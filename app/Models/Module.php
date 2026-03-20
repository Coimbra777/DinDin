<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    public static function getModules()
    {
        return static::query()
            ->whereRaw('LENGTH(father_path) = 0 OR father_path IS NULL ')
            ->orderBy('father_order', 'ASC')
            ->get();
    }

    /**
     * The module's submodules
     */
    public function submodules()
    {
        return $this->hasMany(self::class, 'father_path', 'path')->orderBy('order');
    }

    /**
     * The events that belong to the usergroup.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_module');
    }
}

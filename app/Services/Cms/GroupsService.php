<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Group;

class GroupsService
{
    public function store(array $data): Group
    {
        $moduleIds = $data['module_id'] ?? [];
        unset($data['module_id']);

        $group = Group::query()->create($data);

        if ($moduleIds !== []) {
            $group->modules()->attach($moduleIds);
        }

        return $group;
    }

    public function update(Group $group, array $data): void
    {
        $moduleIds = $data['module_id'] ?? [];
        unset($data['module_id']);

        $group->update($data);
        $group->modules()->sync(is_array($moduleIds) ? $moduleIds : []);
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $groups = Group::query()->whereIn('id', $ids)->get();
        foreach ($groups as $group) {
            $group->modules()->detach();
            $group->delete();
        }
    }
}

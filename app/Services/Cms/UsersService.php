<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class UsersService
{
    public function __construct(
        private readonly CmsImageUploadService $imageUploadService,
    ) {
    }

    public function store(array $data, ?UploadedFile $imageFile): User
    {
        $data['password'] = bcrypt($data['password']);
        unset($data['image']);
        $data['image'] = null;

        if ($imageFile !== null) {
            $path = $this->imageUploadService->upload('users', $imageFile);
            if ($path === false) {
                throw new \RuntimeException('image cannot be uploaded');
            }
            $data['image'] = $path;
        }

        return User::query()->create($data);
    }

    public function update(User $user, array $data, ?UploadedFile $imageFile): void
    {
        unset($data['id'], $data['image']);

        $image = $user->image;
        if ($imageFile !== null) {
            $uploaded = $this->imageUploadService->upload('users', $imageFile);
            if ($uploaded !== false) {
                if (!empty($user->image)) {
                    $this->imageUploadService->remove($user->image);
                }
                $image = $uploaded;
            }
        }

        $data['image'] = $image;

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function deleteMany(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $users = User::query()->whereIn('id', $ids)->get();
        foreach ($users as $user) {
            if (!empty($user->image)) {
                $this->imageUploadService->remove($user->image);
            }
            $user->delete();
        }
    }
}

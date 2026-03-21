<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Dados partilhados com as views CMS (menu lateral, logos, request atual).
 */
final class CmsShellDataService
{
    /**
     * @return array{request: Request, logos: array{logo200: string, logo50: string}, modules: mixed}
     */
    public function getData(User $user, Request $request): array
    {
        return [
            'request' => $request,
            'logos' => $this->logos(),
            'modules' => $this->menuForUser($user),
        ];
    }

    /**
     * @return array{logo200: string, logo50: string}
     */
    private function logos(): array
    {
        $headerLogo = config('cms.header_logo', 'img/logo_cms.svg');

        return [
            'logo200' => asset($headerLogo),
            'logo50' => asset($headerLogo),
        ];
    }

    private function menuForUser(User $user): mixed
    {
        $group = $user->group;

        return $group instanceof Group ? $group->menu() : [];
    }
}

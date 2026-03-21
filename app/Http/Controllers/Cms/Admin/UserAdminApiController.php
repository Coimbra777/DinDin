<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Admin\AdminUpdateUserModulesApiRequest;
use App\Models\SaasModule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAdminApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $search = trim((string) $request->query('q', ''));

        $q = User::query()
            ->select(['id', 'name', 'email', 'is_admin'])
            ->orderBy('name');

        if ($search !== '') {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return response()->json($q->paginate($perPage));
    }

    public function show(User $user): JsonResponse
    {
        $user->load('saasModules:id,name,slug');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'module_ids' => $user->saasModules->pluck('id')->values()->all(),
            ],
            'all_modules' => SaasModule::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->values(),
        ]);
    }

    public function updateModules(AdminUpdateUserModulesApiRequest $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id && $user->isAdmin() && ! $request->boolean('is_admin')) {
            return response()->json([
                'message' => 'Não pode remover a sua própria flag de administrador.',
            ], 422);
        }

        $user->update([
            'is_admin' => $request->boolean('is_admin'),
        ]);
        $user->saasModules()->sync($request->input('modules', []));

        $user->refresh()->load('saasModules:id,name,slug');

        return response()->json([
            'message' => 'Alterações guardadas.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'module_ids' => $user->saasModules->pluck('id')->values()->all(),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Category;
use App\Services\Finance\CategoryApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CategoryApiController extends RestrictedController
{
    public function __construct(
        private readonly CategoryApiService $categories,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->categories->listForUser((int) $request->user()->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->categories->validationRulesForStore());
        $c = $this->categories->create((int) $request->user()->id, $data);

        return response()->json($this->categories->toArray($c), 201);
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $c = Category::forUser($request->user()->id)->where('id', $category)->firstOrFail();
        $data = $request->validate($this->categories->validationRulesForUpdate());
        if (array_key_exists('type', $data) && $data['type'] === null) {
            unset($data['type']);
        }
        $c = $this->categories->update($c, $data);

        return response()->json($this->categories->toArray($c));
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $c = Category::forUser($request->user()->id)->where('id', $category)->firstOrFail();
        $this->categories->delete($c);

        return response()->json(['ok' => true]);
    }
}

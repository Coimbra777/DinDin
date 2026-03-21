<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Http\Requests\Finance\DuplicateTransactionRequest;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionApiController extends RestrictedController
{
    public function __construct(
        private readonly TransactionApiService $transactions,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'month' => Transaction::normalizeMonth($request->query('month')),
        ];
        if ($request->filled('category_id')) {
            $filters['category_id'] = (int) $request->query('category_id');
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $payload = $this->transactions->listForUserPaginated(
            (int) $request->user()->id,
            $filters,
            $perPage,
            $page,
        );

        return response()->json($payload);
    }

    public function recent(Request $request): JsonResponse
    {
        $month = Transaction::normalizeMonth($request->query('month'));
        $data = $this->transactions->recentForUser((int) $request->user()->id, $month);

        return response()->json(['data' => $data]);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $payload = $this->transactions->create(
            (int) $request->user()->id,
            $request->toTransactionAttributes(),
        );

        return response()->json($payload, 201);
    }

    public function update(UpdateTransactionRequest $request, int $transaction): JsonResponse
    {
        $t = Transaction::forUser($request->user()->id)->where('id', $transaction)->firstOrFail();
        $payload = $this->transactions->update(
            $t,
            $request->toTransactionAttributes(),
        );

        return response()->json($payload);
    }

    public function duplicate(DuplicateTransactionRequest $request, int $transaction): JsonResponse
    {
        $t = Transaction::forUser($request->user()->id)->where('id', $transaction)->firstOrFail();
        $created = $this->transactions->duplicateFollowingMonths($t, $request->months());

        return response()->json([
            'data' => $created,
            'count' => count($created),
        ], 201);
    }

    public function destroy(Request $request, int $transaction): JsonResponse
    {
        $t = Transaction::forUser($request->user()->id)->where('id', $transaction)->firstOrFail();
        $this->transactions->delete($t);

        return response()->json(['ok' => true]);
    }
}

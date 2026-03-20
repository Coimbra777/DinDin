<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
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

        $data = $this->transactions->listForUser((int) $request->user()->id, $filters);

        return response()->json(['data' => $data]);
    }

    public function recent(Request $request): JsonResponse
    {
        $month = Transaction::normalizeMonth($request->query('month'));
        $data = $this->transactions->recentForUser((int) $request->user()->id, $month);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->transactions->validatePayload($request);
        $payload = $this->transactions->create((int) $request->user()->id, $data);

        return response()->json($payload, 201);
    }

    public function update(Request $request, int $transaction): JsonResponse
    {
        $t = Transaction::forUser($request->user()->id)->where('id', $transaction)->firstOrFail();
        $data = $this->transactions->validatePayload($request);
        $payload = $this->transactions->update($t, $data);

        return response()->json($payload);
    }

    public function destroy(Request $request, int $transaction): JsonResponse
    {
        $t = Transaction::forUser($request->user()->id)->where('id', $transaction)->firstOrFail();
        $this->transactions->delete($t);

        return response()->json(['ok' => true]);
    }
}

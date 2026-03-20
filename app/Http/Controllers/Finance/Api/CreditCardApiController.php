<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\CreditCard;
use App\Services\Finance\CreditCardBillingService;
use App\Services\Finance\CreditCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditCardApiController extends RestrictedController
{
    public function __construct(
        private readonly CreditCardService $creditCards,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->creditCards->listForUser((int) $request->user()->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'limit' => 'required|numeric|min:0.01',
            'closing_day' => 'required|integer|min:1|max:31',
            'due_day' => 'required|integer|min:1|max:31',
        ]);

        $c = $this->creditCards->create((int) $request->user()->id, $data);

        return response()->json($this->creditCards->toArray($c), 201);
    }

    public function update(Request $request, CreditCard $finance_credit_card): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'limit' => 'required|numeric|min:0.01',
            'closing_day' => 'required|integer|min:1|max:31',
            'due_day' => 'required|integer|min:1|max:31',
        ]);

        $c = $this->creditCards->update($finance_credit_card, $data);

        return response()->json($this->creditCards->toArray($c));
    }

    public function destroy(Request $request, CreditCard $finance_credit_card): JsonResponse
    {
        $this->creditCards->delete($finance_credit_card);

        return response()->json(['ok' => true]);
    }

    public function bill(Request $request, CreditCard $finance_credit_card): JsonResponse
    {
        return response()->json(CreditCardBillingService::billPayload($finance_credit_card));
    }
}

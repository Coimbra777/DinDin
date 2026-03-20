<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends RestrictedController
{
    public function index(Request $request): View
    {
        $month = Transaction::normalizeMonth($request->query('month'));
        $initialView = 'transactions';

        return view('cms.finance.spa', compact('initialView', 'month'));
    }

    public function create(Request $request): View
    {
        $headers = $this->headers('Nova transação', [
            ['icon' => '', 'title' => 'Finanças', 'url' => route('finance_dashboard.index')],
            ['icon' => '', 'title' => 'Transações', 'url' => route('finance_transactions.index')],
            ['icon' => '', 'title' => 'Nova', 'url' => ''],
        ]);

        $categories = Category::forUser($request->user()->id)->orderBy('name')->get();

        return view('cms.finance.transactions.create', compact('headers', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedTransaction($request);
        $data['user_id'] = $request->user()->id;

        $record = Transaction::create($data);
        $m = Transaction::normalizeMonth($record->transaction_date->format('Y-m'));

        return redirect()
            ->route('finance_dashboard.index', ['month' => $m])
            ->with('message', 'Transação registada.');
    }

    public function edit(Request $request, Transaction $finance_transaction): View
    {
        $headers = $this->headers('Editar transação', [
            ['icon' => '', 'title' => 'Finanças', 'url' => route('finance_dashboard.index')],
            ['icon' => '', 'title' => 'Transações', 'url' => route('finance_transactions.index')],
            ['icon' => '', 'title' => 'Editar', 'url' => ''],
        ]);

        $categories = Category::forUser($request->user()->id)->orderBy('name')->get();

        return view('cms.finance.transactions.edit', compact('headers', 'finance_transaction', 'categories'));
    }

    public function update(Request $request, Transaction $finance_transaction): RedirectResponse
    {
        $data = $this->validatedTransaction($request);
        $finance_transaction->update($data);
        $m = Transaction::normalizeMonth($finance_transaction->transaction_date->format('Y-m'));

        return redirect()
            ->route('finance_transactions.index', ['month' => $m])
            ->with('message', 'Transação atualizada.');
    }

    public function destroy(Request $request, Transaction $finance_transaction): RedirectResponse
    {
        $m = Transaction::normalizeMonth($finance_transaction->transaction_date->format('Y-m'));
        $finance_transaction->delete();

        return redirect()
            ->route('finance_transactions.index', ['month' => $m])
            ->with('message', 'Transação removida.');
    }

    private function validatedTransaction(Request $request): array
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', Rule::in([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE])],
            'category_id' => [
                'nullable',
                Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:5000',
            'installment_number' => ['nullable', 'integer', 'min:1', 'max:360'],
            'installment_of' => ['nullable', 'integer', 'min:2', 'max:360'],
            'credit_card_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_credit_cards', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
        ]);

        $hasN = array_key_exists('installment_number', $data) && $data['installment_number'] !== null;
        $hasO = array_key_exists('installment_of', $data) && $data['installment_of'] !== null;
        if ($hasN xor $hasO) {
            throw ValidationException::withMessages([
                'installment_number' => 'Informe parcela atual e total ou deixe os dois em branco.',
            ]);
        }
        if ($hasN && $hasO && (int) $data['installment_number'] >= (int) $data['installment_of']) {
            throw ValidationException::withMessages([
                'installment_number' => 'A parcela atual deve ser menor que o total de parcelas.',
            ]);
        }

        $hasCard = ! empty($data['credit_card_id']);
        if ($hasCard && $data['type'] !== Transaction::TYPE_EXPENSE) {
            throw ValidationException::withMessages([
                'credit_card_id' => 'Cartão de crédito só pode ser usado em despesas.',
            ]);
        }
        if ($hasCard) {
            $data['is_credit_card'] = true;
        } else {
            $data['credit_card_id'] = null;
            $data['is_credit_card'] = false;
        }

        return $data;
    }
}

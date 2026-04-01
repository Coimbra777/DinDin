<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Category;
use App\Services\Finance\CategoryApiService;
use App\Services\Finance\FinanceReadCache;
use App\Services\Finance\FinancialSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends RestrictedController
{
    public function __construct(
        private readonly CategoryApiService $categoryApi,
        private readonly FinancialSummaryService $summaries,
        private readonly FinanceReadCache $readCache,
    ) {
        parent::__construct();
    }

    public function index(Request $request): View
    {
        if (config('finance.standalone_ui', true)) {
            $mq = $request->query('month');
            $month = $this->summaries->normalizeMonth(is_string($mq) ? $mq : null);
            $initialView = 'categories';

            return view('cms.finance.spa', compact('initialView', 'month'));
        }

        $headers = $this->headers('Categorias (Finanças)', [
            ['icon' => '', 'title' => 'Finanças', 'url' => route('finance_dashboard.index')],
            ['icon' => '', 'title' => 'Categorias', 'url' => ''],
        ]);

        $categories = Category::forUser($request->user()->id)
            ->orderBy('name')
            ->paginate(20);

        $totalsByCategory = $this->summaries->totalsByCategoryForUser((int) $request->user()->id);

        return view('cms.finance.categories.index', compact('headers', 'categories', 'totalsByCategory'));
    }

    public function create(): View
    {
        $headers = $this->headers('Nova categoria', [
            ['icon' => '', 'title' => 'Finanças', 'url' => route('finance_dashboard.index')],
            ['icon' => '', 'title' => 'Categorias', 'url' => route('finance_categories.index')],
            ['icon' => '', 'title' => 'Nova', 'url' => ''],
        ]);

        return view('cms.finance.categories.create', compact('headers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:7',
            'type' => ['nullable', Rule::in([Category::TYPE_INCOME, Category::TYPE_EXPENSE])],
            'group' => ['nullable', 'string', 'max:32', Rule::in([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ])],
        ]);

        Category::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? Category::TYPE_EXPENSE,
            'group' => $data['group'] ?? null,
            'color' => $data['color'] ?? null,
        ]);
        $this->readCache->bump((int) $request->user()->id);

        return redirect()
            ->route('finance_categories.index')
            ->with('message', 'Categoria criada.');
    }

    public function edit(Request $request, Category $finance_category): View
    {
        $headers = $this->headers('Editar categoria', [
            ['icon' => '', 'title' => 'Finanças', 'url' => route('finance_dashboard.index')],
            ['icon' => '', 'title' => 'Categorias', 'url' => route('finance_categories.index')],
            ['icon' => '', 'title' => 'Editar', 'url' => ''],
        ]);

        return view('cms.finance.categories.edit', compact('headers', 'finance_category'));
    }

    public function update(Request $request, Category $finance_category): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:7',
            'type' => ['nullable', Rule::in([Category::TYPE_INCOME, Category::TYPE_EXPENSE])],
            'group' => ['nullable', 'string', 'max:32', Rule::in([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ])],
        ]);
        if (array_key_exists('type', $data) && $data['type'] === null) {
            unset($data['type']);
        }

        try {
            $this->categoryApi->update($finance_category, $data);
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('finance_categories.index')
            ->with('message', 'Categoria atualizada.');
    }

    public function destroy(Request $request, Category $finance_category): RedirectResponse
    {
        $userId = (int) $finance_category->user_id;
        $finance_category->delete();
        $this->readCache->bump($userId);

        return redirect()
            ->route('finance_categories.index')
            ->with('message', 'Categoria removida.');
    }
}

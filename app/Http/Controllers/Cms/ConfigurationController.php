<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Configurations;
use App\Http\Requests\Cms\UpdateConfigurationRequest;
use App\Services\Cms\ConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigurationController extends RestrictedController
{
    public function __construct(
        private readonly ConfigurationService $configurationService,
    ) {
        parent::__construct();
    }

    /**
     * Formulário de edição das configurações globais.
     */
    public function index(Request $request): View
    {
        $headers = parent::headers(
            'Configurações',
            [
                ['icon' => '', 'title' => 'Configurações', 'url' => route('configurations.index')],
            ]
        );

        $configurations = Configurations::query()->findOrFail(1);

        return view('cms.configurations.edit', compact('headers', 'configurations'));
    }

    /**
     * Atualiza configurações (mesma rota e mensagem de sucesso do legado).
     */
    public function update(UpdateConfigurationRequest $request, $configuration): RedirectResponse
    {
        $model = Configurations::query()->findOrFail($configuration);

        $this->configurationService->update($model, $request->validated());

        return redirect()
            ->route('configurations.index')
            ->with('message', 'Registro atualizado com sucesso!');
    }
}

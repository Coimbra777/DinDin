<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Configurations;

class ConfigurationService
{
    /**
     * Atualiza registro de configurações globais (id fixo vindo da rota).
     *
     * @param  array<string, mixed>  $data  Dados já validados (ex.: $request->validated()).
     */
    public function update(Configurations $configuration, array $data): void
    {
        $configuration->update($data);
    }
}

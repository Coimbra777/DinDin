<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Clients;

class ClientsService
{
    public function update(Clients $client, array $data): void
    {
        $client->update($data);
    }
}

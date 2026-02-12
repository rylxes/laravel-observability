<?php

namespace Rylxes\Observability\Filament\Resources\QueryLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Rylxes\Observability\Filament\Resources\QueryLogResource;

class ListQueryLogs extends ListRecords
{
    protected static string $resource = QueryLogResource::class;
}

<?php

namespace Rylxes\Observability\Filament\Resources\RequestTraceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Rylxes\Observability\Filament\Resources\RequestTraceResource;

class ListRequestTraces extends ListRecords
{
    protected static string $resource = RequestTraceResource::class;
}

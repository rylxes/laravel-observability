<?php

namespace Rylxes\Observability\Filament\Resources\ExceptionLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Rylxes\Observability\Filament\Resources\ExceptionLogResource;

class ListExceptionLogs extends ListRecords
{
    protected static string $resource = ExceptionLogResource::class;
}

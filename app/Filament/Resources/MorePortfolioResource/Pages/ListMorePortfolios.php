<?php

namespace App\Filament\Resources\MorePortfolioResource\Pages;

use App\Filament\Resources\MorePortfolioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMorePortfolios extends ListRecords
{
    protected static string $resource = MorePortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

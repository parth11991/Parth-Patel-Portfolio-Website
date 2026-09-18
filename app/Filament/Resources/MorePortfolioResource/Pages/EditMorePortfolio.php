<?php

namespace App\Filament\Resources\MorePortfolioResource\Pages;

use App\Filament\Resources\MorePortfolioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMorePortfolio extends EditRecord
{
    protected static string $resource = MorePortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->requiresConfirmation(),
        ];
    }
}

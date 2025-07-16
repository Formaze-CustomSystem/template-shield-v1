<?php

namespace App\Filament\Resources\DataMaster\UserResource\Pages;

use App\Filament\Resources\DataMaster\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}

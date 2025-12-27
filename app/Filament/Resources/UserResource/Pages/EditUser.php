<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->record;
        if ($user) {
            if (!array_key_exists('password', $data) || empty($data['password'])) {
                $data['password'] = $user->password;
            }
            // Prevent non-super-admins from changing signature_collection_id
            if (!auth()->user()?->hasRole('super_admin')) {
                $data['signature_collection_id'] = $user->signature_collection_id;
            }
        }
        return $data;
    }

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.title.edit');
    }

    protected function getActions(): array
    {
        $ret[] = DeleteAction::make();

        return $ret;
    }
}

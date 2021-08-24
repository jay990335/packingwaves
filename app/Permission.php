<?php

namespace App;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as ModelsPermission;

class Permission extends ModelsPermission
{
    public static function defaultPermissions()
    {
        return [
            'view role',
            'create role',
            'edit role',
            'delete role',

            'view user',
            'create user',
            'edit user',
            'delete user',

            'view linnworks user',
            'create linnworks user',
            'edit linnworks user',
            'delete linnworks user',

            'view Print Buttons',
            'create Print Buttons',
            'edit Print Buttons',
            'delete Print Buttons',

            'view folders setting',
            'create folders setting',
            'edit folders setting',
            'delete folders setting',
        ];
    }

    public function isDeleteLabel()
    {
        return Str::contains($this->name, 'delete') ? 'text-danger' : null;
    }
}

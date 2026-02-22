<?php

namespace App\Http\Controllers;

use App\Models\Role;

class RoleController extends BaseController
{
    public function index()
    {
        return $this->sendResponse(Role::all(), 'Roles retrieved successfully.');
    }
}

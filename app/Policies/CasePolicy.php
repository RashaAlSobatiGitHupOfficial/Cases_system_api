<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CaseModel;

class CasePolicy
{
    public function viewAll(User $user)
    {
        return $user->hasPermission('cases.view_all');
    }

    public function viewAssigned(User $user)
    {
        return $user->hasPermission('cases.view_assigned');
    }

    public function viewUnassigned(User $user)
    {
        return $user->hasPermission('cases.view_unassigned');
    }

    public function assign(User $user)
    {
        return $user->hasPermission('cases.assign');
    }

    // --------- IMPORTANT ----------
    public function assignToMe(User $user)
    {
        return $user->hasPermission('cases.assign');
    }

    public function accept(User $user)
    {
        return $user->hasPermission('cases.accept');
    }

    public function reassign(User $user)
    {
        return $user->hasPermission('cases.reassign');
    }

    public function removeEmployee(User $user)
    {
        return $user->hasPermission('cases.remove_employee');
    }

    public function edit(User $user)
    {
        return $user->hasPermission('cases.edit');
    }

    public function delete(User $user)
    {
        return $user->hasPermission('cases.delete');
    }
}

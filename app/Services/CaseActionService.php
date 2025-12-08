<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\User;

class CaseActionService
{
    public function getAllowedActions(CaseModel $case, User $user)
    {
        return [
            'assign_to_me' => $user->can('assignToMe', $case),
            'accept'       => $user->can('accept', $case),
            'reassign'     => $user->can('reassign', $case),
            'remove_employee' => $user->can('removeEmployee', $case),
            'close'        => $user->can('close', $case),
        ];
    }
}

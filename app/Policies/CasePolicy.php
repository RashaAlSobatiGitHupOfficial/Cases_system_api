<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CaseModel;

class CasePolicy
{
    public function assignToMe(User $user, CaseModel $case)
    {
        if (!$user->employee) return false;

        if ($case->status === 'closed') return false;

        // If someone already accepted the case, no one else can assign
        $accepted = $case->employees()
                        ->wherePivot('action', 'accepted')
                        ->exists();

        if ($accepted) return false;

        return true;
    }

    public function accept(User $user, CaseModel $case)
    {
        return $case->employees()
            ->where('employee_id', $user->employee->id)
            ->exists();
    }

    public function reassign(User $user, CaseModel $case)
    {
        if ($case->status === 'closed') return false;

        return $user->hasPermission('cases.reassign');
    }

    public function removeEmployee(User $user, CaseModel $case)
    {
        if ($case->status === 'closed') return false;

        return $user->hasPermission('cases.remove_employee');
    }

    public function close(User $user, CaseModel $case)
    {
        if ($case->status === 'closed') return false;

        return $user->hasPermission('cases.close')
            || $case->employees()
                ->where('employee_id', $user->employee->id)
                ->wherePivot('action', 'accepted')
                ->exists();
    }


}

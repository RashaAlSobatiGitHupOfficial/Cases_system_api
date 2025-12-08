<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CaseModel;

class CasePolicy
{
    public function assignToMe(User $user, CaseModel $case)
    {
        if (!$user->employee) return false;
        if (!$user->hasPermission('cases.assign_to_me')) return false;


        if ($case->status === 'closed') return false;

        $accepted = $case->employees()
                        ->wherePivot('action', 'accepted')
                        ->exists();

        if ($accepted) return false;

        return true;
    }

    public function accept(User $user, CaseModel $case)
    {
        if (!$user->hasPermission('cases.accept')) return false;
        if (!$user->employee) return false;

        // must be assigned
        $assigned = $case->employees()
            ->where('employee_id', $user->employee->id)
            ->exists();
        if (!$assigned) return false;

        // must be in allowed statuses
        if (!in_array($case->status, ['assigned', 'reassigned'])) return false;

        // someone else already accepted
        $alreadyAccepted = $case->employees()
            ->wherePivot('action', 'accepted')
            ->where('employee_id', '!=', $user->employee->id)
            ->exists();
        if ($alreadyAccepted) return false;

        return true;
    }



    public function reassign(User $user, CaseModel $case)
{
    if ($case->status === 'closed') {
        return false;
    }

    if (!$user->hasPermission('cases.reassign')) {
        return false;
    }

    if (!$user->employee) {
        return false;
    }

    $isAssigned = $case->employees()
        ->where('employee_id', $user->employee->id)
        ->exists();

    return $isAssigned;
}


    public function removeEmployee(User $user, CaseModel $case)
    {
        if ($case->status === 'closed') return false;

        return $user->hasPermission('cases.remove_employee');
    }

    public function close(User $user, CaseModel $case)
    {
        if ($case->status !== 'in_progress') return false;

        return $user->hasPermission('cases.close')
            || $case->employees()
                ->where('employee_id', $user->employee->id)
                ->wherePivot('action', 'accepted')
                ->exists();
    }


}

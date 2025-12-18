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

    // Must be assigned
    $assigned = $case->employees()
        ->where('employee_id', $user->employee->id)
        ->exists();
    if (!$assigned) return false;

    // Must NOT be closed
    if ($case->status === 'closed') return false;

    // Must NOT be already accepted by this employee
    $alreadyAcceptedByThisEmployee = $case->employees()
        ->where('employee_id', $user->employee->id)
        ->wherePivot('action', 'accepted')
        ->exists();

    if ($alreadyAcceptedByThisEmployee) {
        return false; // hide accept button
    }

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
    // الحالة يجب أن تكون in_progress
    if ($case->status !== 'in_progress') {
        return false;
    }

    // المستخدم يجب أن يكون لديه بروفايل موظف
    if (!$user->employee) {
        return false;
    }

    // الموظف يجب أن يكون قد قبل الحالة
    $acceptedByUser = $case->employees()
        ->where('employee_id', $user->employee->id)
        ->wherePivot('action', 'accepted')
        ->exists();

    return $acceptedByUser;
}



}

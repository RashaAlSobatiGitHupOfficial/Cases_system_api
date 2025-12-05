<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseEmployee;
use App\Models\CaseLog;

class CaseWorkflowService
{
    public function assignToMe(CaseModel $case, $user)
    {
        $employeeId = $user->employee->id;

        // Employee already assigned?
        if ($case->employees()->where('employee_id', $employeeId)->exists()) {
            return $this->returnCase($case);
        }

        $case->employees()->attach($employeeId, [
            'action'      => 'assigned',
            'assigned_by' => $user->id,
            'is_primary'  => true,
            'started_at'  => now(),
        ]);

        $case->update(['status' => 'assigned']);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'assign_to_me',
            'new_value' => $employeeId
        ]);

        return $this->returnCase($case);
    }

    public function acceptCase(CaseModel $case, $user)
    {
        $employeeId = $user->employee->id;

        if (!$case->employees()->where('employee_id', $employeeId)->exists()) {
            abort(403, 'You are not assigned to this case.');
        }

        $case->employees()->updateExistingPivot($employeeId, [
            'action' => 'accepted',
            'started_at' => now()
        ]);

        $case->update(['status' => 'in_progress']);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'case_accepted'
        ]);

        return $this->returnCase($case);
    }

    public function reassign(CaseModel $case, $user, $newEmployeeId)
    {
        // 1. Get current active primary employee
        $current = $case->employees()
            ->wherePivot('is_primary', true)
            ->first();

        // 2. Close old assignment (if exists)
        if ($current) {
            $case->allEmployees()->updateExistingPivot($current->id, [
                'ended_at'   => now(),
                'action'     => 'reassigned',
                'is_primary' => false
            ]);
        }

        // 3. Attach new primary employee
        $case->allEmployees()->attach($newEmployeeId, [
            'is_primary'  => true,
            'action'      => 'assigned',
            'assigned_by' => $user->id,
            'started_at'  => now(),
            'ended_at'    => null
        ]);

        // 4. Update Case status
        $case->update(['status' => 'reassigned']);

        // 5. Add Case Log
        CaseLog::create([
            'case_id'   => $case->id,
            'user_id'   => $user->id,
            'action'    => 'reassigned',
            'old_value' => $current ? $current->id : null,
            'new_value' => $newEmployeeId
        ]);

        // 6. Return refreshed case with ACTIVE employees only
        return $case->fresh()->load([
            'client',
            'priority',
            'employees'
        ]);
    }

    public function removeEmployee(CaseModel $case, $user, $employeeId)
    {
        $case->employees()->updateExistingPivot($employeeId, [
            'ended_at' => now(),
            'action'   => 'removed'
        ]);

        $case->employees()->detach($employeeId);

        // If no employees remain → status becomes opened
        if ($case->employees()->count() === 0) {
            $case->update(['status' => 'opened']);
        }

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'removed_employee',
            'old_value' => $employeeId
        ]);

        return $this->returnCase($case);
    }

    public function closeCase(CaseModel $case, $user)
    {
        $case->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'closed'
        ]);

        return $this->returnCase($case);
    }

    private function returnCase(CaseModel $case)
    {
        return $case->fresh()->load([
            'client',
            'priority',
            'employees'
        ]);
    }
}

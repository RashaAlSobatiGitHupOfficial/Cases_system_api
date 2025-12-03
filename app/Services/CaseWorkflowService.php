<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseEmployee;
use App\Models\CaseLog;
use Illuminate\Http\Request;

class CaseWorkflowService
{
    /** Assign case to logged-in employee **/
    public function assignToMe(CaseModel $case, $user)
    {
        $employeeId = $user->employee->id;

        // Prevent duplicate
        if ($case->employees()->where('employee_id', $employeeId)->exists()) {
            return $case; 
        }

        $case->employees()->attach($employeeId, [
            'action' => 'assigned',
            'assigned_by' => $user->id,
            'is_primary' => true,
            'started_at' => now(),
        ]);

        $case->update(['status' => 'assigned']);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'assign_to_me',
            'new_value' => "Assigned to employee $employeeId"
        ]);

        return $case->fresh();
    }

    /** Accept case => IN PROGRESS **/
    public function acceptCase(CaseModel $case, $user)
{
    $employeeId = $user->employee->id;

    // Update pivot
    $case->employees()->updateExistingPivot($employeeId, [
        'action' => 'accepted',
        'started_at' => now(),
    ]);

    $case->update(['status' => 'in_progress']);

    CaseLog::create([
        'case_id' => $case->id,
        'user_id' => $user->id,
        'action'  => 'case_accepted'
    ]);

    return $case->fresh();
}


    /** Reassign **/
    public function reassign(CaseModel $case, $user, $newEmp)
    {
        // End old assignment
        $oldEmployee = $case->employees()->first();

        if ($oldEmployee) {
            $case->employees()->updateExistingPivot($oldEmployee->id, [
                'ended_at' => now(),
                'action' => 'reassigned'
            ]);
        }

        // Assign new
        $case->employees()->attach($newEmp, [
            'action' => 'assigned',
            'assigned_by' => $user->id,
            'started_at' => now(),
            'is_primary' => true
        ]);

        $case->update(['status' => 'reassigned']);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'reassigned',
            'new_value' => "Assigned to employee $newEmp"
        ]);

        return $case->fresh();
    }

    /** Remove employee **/
    public function removeEmployee(CaseModel $case, $user, $employeeId)
    {
        $case->employees()->updateExistingPivot($employeeId, [
            'action' => 'removed',
            'ended_at' => now()
        ]);

        $case->employees()->detach($employeeId);

        CaseLog::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'action'  => 'removed_employee',
            'old_value' => "Employee $employeeId removed"
        ]);

        return $case->fresh();
    }
}

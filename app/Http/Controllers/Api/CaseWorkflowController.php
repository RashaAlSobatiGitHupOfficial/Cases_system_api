<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Services\CaseWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CaseWorkflowController extends Controller
{
    use AuthorizesRequests;

    protected $service;

    public function __construct(CaseWorkflowService $service)
    {
        $this->service = $service;
    }

    public function assignToMe(CaseModel $case, Request $request)
    {
        $this->authorize('assignToMe', $case);

        $result = $this->service->assignToMe($case, $request->user());

        return response()->json([
            'message' => 'Case assigned to you.',
            'case' => $result
        ]);
    }

    public function accept(CaseModel $case, Request $request)
    {
        $this->authorize('accept', $case);

        $result = $this->service->acceptCase($case, $request->user());

        return response()->json([
            'message' => 'Case accepted.',
            'case' => $result
        ]);
    }

    public function reassign(CaseModel $case, Request $request)
    {
        $this->authorize('reassign', $case);

        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $result = $this->service->reassign(
            $case,
            $request->user(),
            $request->employee_id
        );

        return response()->json([
            'message' => 'Case reassigned.',
            'case' => $result
        ]);
    }

    public function removeEmployee(CaseModel $case, Request $request)
    {
        $this->authorize('removeEmployee', $case);

        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $result = $this->service->removeEmployee(
            $case,
            $request->user(),
            $request->employee_id
        );

        return response()->json([
            'message' => 'Employee removed.',
            'case' => $result
        ]);
    }
}

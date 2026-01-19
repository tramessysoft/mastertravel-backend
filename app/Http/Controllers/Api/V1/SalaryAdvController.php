<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OfficeLedger;
use Illuminate\Http\Request;
use App\Models\SalaryAdvanced;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SalaryAdvController extends Controller
{
    public function index()
    {
        $model = SalaryAdvanced::all();

        return response()->json([
            'status' => 'Success',
            'data' => $model
        ], 200);
    }

    public function store(Request $request)
    {


        DB::beginTransaction();

        try {
            // Create Salary Advance entry
            $salary = SalaryAdvanced::create([
                'user_id'      => Auth::id(),
                'date'         => $request->date,
                'employee_id'         => $request->employee_id,
                'amount'         => $request->amount,
                'branch_name'  => $request->branch_name,
                'adjustment'         => $request->adjustment,
                'salary_month'         => $request->salary_month,
                'remarks'      => $request->remarks,
                'status'      => $request->status,
                'created_by'   => $request->created_by
            ]);

            // Create Office Ledger entry
            OfficeLedger::create([
                'user_id'      => Auth::id(),
                'date'         => $request->date,
                'advanced_id'  => $salary->id,
                'branch_name'  => $request->branch_name,
                'cash_out'     => $request->amount,
                'remarks'      => $request->remarks,
                'created_by'   => $request->created_by
            ]);

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Salary advance created successfully.',
                'data' => $salary
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'Error',
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $data = SalaryAdvanced::findOrFail($id);
        return response()->json([
            'status' => 'Success',
            'data' => $data
        ], 200);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find the Salary Advance entry
            $salary = SalaryAdvanced::findOrFail($id);

            // Update Salary Advance fields
            $salary->update([
                'date'         => $request->date,
                'employee_id'         => $request->employee_id,
                'amount'         => $request->amount,
                'branch_name'  => $request->branch_name,
                'adjustment'         => $request->adjustment,
                'salary_month'         => $request->salary_month,
                'remarks'      => $request->remarks,
                'status'      => $request->status,
                'created_by'   => $request->created_by
            ]);

            // Update related Office Ledger record (if exists)
            $ledger = OfficeLedger::where('advanced_id', $salary->id)->first();

            if ($ledger) {
                $ledger->update([
                    'date'         => $request->date,
                    'branch_name'  => $request->branch_name,
                    'cash_out'     => $request->amount,
                    'remarks'      => $request->remarks,
                    'created_by'   => $request->created_by,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Salary advance updated successfully.',
                'data' => $salary
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'Error',
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        // লোন রেকর্ড খুঁজে নিও
        $salary = SalaryAdvanced::findOrFail($id);

        // সংশ্লিষ্ট OfficeLedger রেকর্ড ডিলিট করো
        OfficeLedger::where('advanced_id', $salary->id)->delete();

        // SalaryAdvanced রেকর্ড ডিলিট করো
        $salary->delete();

        return response()->json([
            'status' => 'Success',
            'message' => 'Salary advance and related ledger deleted successfully.',

        ], 200);
    }
}

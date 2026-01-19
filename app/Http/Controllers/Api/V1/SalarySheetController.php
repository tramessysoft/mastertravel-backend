<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SalarySheet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Salary_items;
use Illuminate\Support\Facades\Auth;

class SalarySheetController extends Controller
{

    public function index()
    {
        $data = SalarySheet::all();
        $data->load('items');
        return response()->json([
            'Success' => true,
            'data' => $data
        ], 200);
    }


    public function store(Request $request)
    {
        // Validate request
     

        // Create Salary Sheet
        $salary = SalarySheet::create([
            'generate_by'    => $request->generate_by,
            'generate_date'  => $request->generate_date,
            'generate_month' => $request->generate_month,
        ]);

        // Insert Salary Items
        foreach ($request->items as $item) {
            Salary_items::create([ // Correct model name
                'salary_id'   => $salary->id,
                'employee_id' => $item['employee_id'],
                'working_day' => $item['working_day'] ?? null,
                'designation' => $item['designation'] ?? null,
                'basic'       => $item['basic'] ?? 0,
                'house_rent'  => $item['house_rent'] ?? 0,
                'conv'        => $item['conv'] ?? 0,
                'medical'     => $item['medical'] ?? 0,
                'allown'      => $item['allown'] ?? 0,
                'bonous'      => $item['bonous'] ?? 0,
                'e_total'     => $item['e_total'] ?? 0,
                'adv'         => $item['adv'] ?? 0,
                'loan'        => $item['loan'] ?? 0,
                'd_total'     => $item['d_total'] ?? 0,
                'net_pay'     => $item['net_pay'] ?? 0,
                'status'      => $item['status'] ,
            ]);
        }

        // Load relationship for response
        $salary->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Salary sheet generated successfully',
            'data'    => $salary
        ]);
    }






    public function show($id)
    {
        $data = SalarySheet::find($id);
           $data->load('items');
        return response()->json($data);
    }

  public function update(Request $request, $id)
{
    // ✅ Validation
    $request->validate([
        'generate_date'  => 'required|date',
        'generate_month' => 'required|string',
        'items'          => 'required|array',
        'items.*.employee_id' => 'required|integer',
    ]);

    // ✅ Find salary sheet
    $salary = SalarySheet::find($id);

    if (!$salary) {
        return response()->json([
            'success' => false,
            'message' => 'Salary sheet not found'
        ], 404);
    }

    // ✅ Update salary sheet info
    $salary->update([
        'generate_date'  => $request->generate_date,
        'generate_month' => $request->generate_month,
    ]);

    // 👉 Frontend থেকে আসা employee_id গুলো রাখি
    $employeeIds = [];

    // ✅ Update / Create salary items
    foreach ($request->items as $item) {

        $employeeIds[] = $item['employee_id'];

        Salary_items::updateOrCreate(
            [
                'salary_id'   => $salary->id,
                'employee_id' => $item['employee_id'],
            ],
            [
                'working_day' => $item['working_day'] ?? null,
                'designation' => $item['designation'] ?? null,
                'basic'       => $item['basic'] ?? 0,
                'house_rent'  => $item['house_rent'] ?? 0,
                'conv'        => $item['conv'] ?? 0,
                'medical'     => $item['medical'] ?? 0,
                'allown'      => $item['allown'] ?? 0,
                'bonous'      => $item['bonous'] ?? 0,
                'e_total'     => $item['e_total'] ?? 0,
                'adv'         => $item['adv'] ?? 0,
                'loan'        => $item['loan'] ?? 0,
                'd_total'     => $item['d_total'] ?? 0,
                'net_pay'     => $item['net_pay'] ?? 0,
                'status'      => $item['status'] ,
            ]
        );
    }

    // 🗑️ Remove deleted employees from salary sheet
    Salary_items::where('salary_id', $salary->id)
        ->whereNotIn('employee_id', $employeeIds)
        ->delete();

    // ✅ Load updated items
    $salary->load('items');

    return response()->json([
        'success' => true,
        'message' => 'Salary sheet updated successfully',
        'data'    => $salary
    ]);
}




public function updateSingle(Request $request, $id)
{
  

    $item = Salary_items::find($id);

    if (!$item) {
        return response()->json([
            'success' => false,
            'message' => 'Salary item not found'
        ], 404);
    }

    // ✅ Update only Salary_items
    $item->update([
        'working_day' => $request->working_day,
        'designation' => $request->designation ,
        'basic'       => $request->basic ,
        'house_rent'  => $request->house_rent ,
        'conv'        => $request->conv ,
        'medical'     => $request->medical ,
        'allown'      => $request->allown ,
        'bonous'      => $request->bonous ,
        'e_total'     => $request->e_total ,
        'adv'         => $request->adv ,
        'loan'        => $request->loan ,
        'd_total'     => $request->d_total ,
        'net_pay'     => $request->net_pay ,
        'status'      => $request->status,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Salary item updated successfully',
        'data'    => $item
    ]);
}













    public function destroy($id)
    {
        // Find the salary sheet
        $salary = SalarySheet::find($id);

        if (!$salary) {
            return response()->json([
                'success' => false,
                'message' => 'Salary sheet not found'
            ], 404);
        }

        // Delete all related salary items (cascade)
        $salary->items()->delete();

        // Delete the salary sheet
        $salary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salary sheet and its items deleted successfully'
        ]);
    }
}

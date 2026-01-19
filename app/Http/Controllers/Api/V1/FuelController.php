<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Fuel;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\SupplierLedger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\FuelLedger;
use Illuminate\Support\Facades\Auth;

class FuelController extends Controller
{
    public function index()
    {
        $fuels = Fuel::latest()->get();
        return response()->json([
            'success' => true,
            'Data' =>  $fuels,
            'message' => ' successfully'
        ], 200);
    }

    public function stock()
    {
        $fuels = FuelLedger::latest()->get();
        return response()->json([
            'success' => true,
            'Data' =>  $fuels,
            'message' => ' successfully'
        ], 200);
    }



    // Store new fuel record


    public function store(Request $request)
    {
        // 1️⃣ Validate incoming request

        DB::beginTransaction();

        try {
            // 2️⃣ Handle image upload
            $image_name = null;
            if ($request->hasFile('image')) {
                $image_name = time() . '.' . $request->file('image')->getClientOriginalExtension();
                $request->file('image')->move(public_path('uploads/purchase'), $image_name);
            }

            // 3️⃣ Create Purchase
            $fuel = Fuel::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'vehicle_no' => $request->vehicle_no,
                'unit_price' => $request->unit_price,
                'quantity' => $request->quantity,
                'fuel_type' => $request->fuel_type,
                'total_cost' => $request->total_cost,
                'supplier_name' => $request->supplier_name,
                'branch_name'  => $request->branch_name,
                'image' => $image_name,

            ]);


            // 5️⃣ Create Supplier Ledger Entry
            SupplierLedger::create([
                'user_id'         => Auth::id(),
                'date'            => $request->date,
                'mode'            => 'Purchase',
                'purchase_id'     => $fuel->id,
                'purchase_amount' => $request->total_cost,
                'supplier_name'   => $request->supplier_name,
                'remarks'         => $request->fuel_type,
            ]);
            // 1️⃣ Get previous stock for this vehicle
            $lastLedger = FuelLedger::where('vehicle_no', $request->vehicle_no)
                ->orderBy('id', 'desc')
                ->first();

            $previousStock = $lastLedger ? $lastLedger->stock : 0;

            // 2️⃣ Calculate new stock after fuel purchase
            $newStock = $previousStock + $request->quantity;

            // 3️⃣ Create FuelLedger
            FuelLedger::create([
                'user_id'    => Auth::id(),
                'date'       => $request->date,
                'fuel_id'    => $fuel->id,
                'vehicle_no' => $request->vehicle_no,
                'in_ltr'     => $request->quantity,
                'amount'     => $request->total_cost,
                'stock'      => $newStock,
            ]);


            // 6️⃣ Create Payment Entry
            Payment::create([
                'user_id'        => Auth::id(),
                'date'           => $request->date,
                'supplier_name'  => $request->supplier_name,
                'purchase_id'    => $fuel->id,
                'total_amount'   => $request->total_cost,
                'pay_amount'     => 0,
                'due_amount'     => $request->total_cost,
                'remarks'        => $request->fuel_type,
                'branch_name'    => $request->branch_name,
                'vehicle_no'     => $request->vehicle_no,
                'status'         => 'pending',

            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully',
                'data'    => $fuel
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // Optional: View single fuel record
    public function show($id)
    {
        $fuel = Fuel::find($id);
        if (!$fuel) {
            return response()->json(['message' => 'Fuel record not found'], 404);
        }
        return response()->json($fuel);
    }

    // Optional: Update fuel record
 public function update(Request $request, $id)
{
   

    DB::beginTransaction();

    try {
        /* ================= Fuel ================= */
        $fuel = Fuel::findOrFail($id);

        /* ================= Image Handling ================= */
        $image_name = $fuel->image;
        if ($request->hasFile('image')) {
            $path = public_path('uploads/purchase');

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            if ($fuel->image && file_exists($path . '/' . $fuel->image)) {
                unlink($path . '/' . $fuel->image);
            }

            $image_name = time() . '_' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->move($path, $image_name);
        }

        /* ================= Numeric Casting ================= */
        $quantity   = (float) $request->quantity;
        $totalCost  = (float) $request->total_cost;

        /* ================= Update Fuel ================= */
        $fuel->update([
            'date'          => $request->date,
            'vehicle_no'    => $request->vehicle_no,
            'unit_price'    => $request->unit_price,
            'quantity'      => $quantity,
            'fuel_type'     => $request->fuel_type,
            'total_cost'    => $totalCost,
            'supplier_name' => $request->supplier_name,
            'branch_name'   => $request->branch_name,
            'image'         => $image_name,
        ]);

        /* ================= Supplier Ledger ================= */
        SupplierLedger::where('purchase_id', $fuel->id)
            ->where('mode', 'Purchase')
            ->update([
                'date'            => $request->date,
                'purchase_amount' => $totalCost,
                'supplier_name'   => $request->supplier_name,
                'remarks'         => $request->fuel_type,
            ]);

        /* ================= Fuel Ledger ================= */
        $lastLedger = FuelLedger::where('vehicle_no', $request->vehicle_no)
            ->where('fuel_id', '!=', $fuel->id)
            ->orderBy('id', 'desc')
            ->first();

        $previousStock = $lastLedger ? (float)$lastLedger->stock : 0;
        $newStock = $previousStock + $quantity;

        FuelLedger::where('fuel_id', $fuel->id)
            ->update([
                'date'       => $request->date,
                'vehicle_no' => $request->vehicle_no,
                'in_ltr'     => $quantity,
                'amount'     => $totalCost,
                'stock'      => $newStock,
            ]);

        /* ================= Payment ================= */
        $payment = Payment::where('purchase_id', $fuel->id)->first();
        if ($payment) {
            $paidAmount = (float) ($payment->pay_amount ?? 0);

            $payment->update([
                'date'          => $request->date,
                'supplier_name' => $request->supplier_name,
                'total_amount'  => $totalCost,
                'due_amount'    => max($totalCost - $paidAmount, 0),
                'remarks'       => $request->fuel_type,
                'branch_name'   => $request->branch_name,
                'vehicle_no'    => $request->vehicle_no,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Fuel purchase updated successfully',
            'data'    => $fuel
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}




    // Optional: Delete
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $fuel = Fuel::findOrFail($id);

            /* 🔹 Delete Image */
            if ($fuel->image) {
                $path = public_path('uploads/purchase/' . $fuel->image);
                if (file_exists($path)) unlink($path);
            }

            /* 🔹 Delete Supplier Ledger */
            SupplierLedger::where('purchase_id', $fuel->id)

                ->delete();

            /* 🔹 Delete Fuel Ledger */
            FuelLedger::where('fuel_id', $fuel->id)->delete();

            /* 🔹 Delete Payment */
            Payment::where('purchase_id', $fuel->id)->delete();

            /* 🔹 Delete Fuel */
            $fuel->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fuel purchase deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

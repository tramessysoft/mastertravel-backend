<?php

namespace App\Http\Controllers\Api\V1;


use App\Models\trip;
use App\Models\FuelLedger;
use App\Models\DriverLedger;
use App\Models\OfficeLedger;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TripController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'super') {


            $trips = Trip::latest()->get();
        } else {
            $trips = Trip::where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json($trips);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Insert into trips table
            $trip = Trip::create([
                'user_id'           => Auth::id(),
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date,
                'branch_name'       => $request->branch_name,
                'start_point'       => $request->start_point,
                'end_point'         => $request->end_point,
                'seat_capacity'     => $request->seat_capacity,
                'coach_no'          => $request->coach_no,
                'trip_id'           => $request->trip_id,
                'bus_no'            => $request->bus_no,
                'driver_name'       => $request->driver_name,
                'supervisor_name'   => $request->supervisor_name,
                'bus_category'      => $request->bus_category,
                'driver_mobile'     => $request->driver_mobile,
                'challan'           => $request->challan,
                'driver_commision'  => $request->driver_commision,
                'helper_commision'  => $request->helper_commision,
                'checker_commision' => $request->checker_commision,
                'supervisor_commision' => $request->supervisor_commision,
                'wash'              => $request->wash,
                'omit_khoraki'      => $request->omit_khoraki,
                'odometer_start'    => $request->odometer_start,
                'odometer_end'      => $request->odometer_end,
                'run_km'            => $request->run_km,
                'kpl'               => $request->kpl,
                'fuel_ltr'          => $request->fuel_ltr,
                'fuel_cost'         => $request->fuel_cost,
                'remarks'           => $request->remarks,
                'food_cost'         => $request->food_cost,
                'total_exp'         => $request->total_exp,
                'total_rent'        => $request->total_rent,
                'advance'           => $request->advance,
                'due_amount'        => $request->due_amount,
                'parking_cost'      => $request->parking_cost,
                'night_guard'       => $request->night_guard,
                'toll_cost'         => $request->toll_cost,
                'feri_cost'         => $request->feri_cost,
                'police_cost'       => $request->police_cost,
                'others_cost'       => $request->others_cost,
                'chada'             => $request->chada,
                'labor'             => $request->labor,
                'status'            => "Pending",
            ]);



            // Insert into branch ledgers
            OfficeLedger::create([
                'user_id'     => Auth::id(),
                'date'        => $request->start_date,
                'unload_point' => $request->unload_point,
                'load_point'  => $request->load_point,
                'customer'    => $request->customer,
                'trip_id'     => $trip->id,
                'branch_name' => $request->branch_name,
                'status'      => "Pending", // fixed
                'cash_out'    => $request->total_exp,
                'cash_in'    => $request->c_adv,
                'remarks'          => $request->remarks,
                'created_by'  => Auth::id(),
            ]);

            $lastLedger = FuelLedger::where('vehicle_no', $request->vehicle_no)
                ->orderBy('id', 'desc')
                ->first();

            $previousStock = $lastLedger ? $lastLedger->stock : 0;

            // 2️⃣ Calculate new stock after fuel usage
            $newStock = $previousStock - $request->req_fuel;

            FuelLedger::create([
                'user_id'    => Auth::id(),
                'date'       => $request->date,
                'trip_id'    => $trip->id,
                'vehicle_no' => $request->vehicle_no,
                'out_ltr'    => $request->req_fuel,
                'stock'      => $newStock,
            ]);





            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip created successfully',
                'data'    => $trip,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function show($id)
    {
        $trip = trip::find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find existing trip
            $trip = Trip::findOrFail($id);

            // Update trip table
            $trip->update([
                'customer'         => $request->customer,
                'buyer_name'         => $request->buyer_name,
                'invoice_no'         => $request->invoice_no,
                'start_date'       => $request->start_date,
                'end_date'         => $request->end_date,
                'branch_name'      => $request->branch_name,
                'load_point'       => $request->load_point,
                'additional_load'  => $request->additional_load,
                'unload_point'     => $request->unload_point,
                'transport_type'   => $request->transport_type,
                'trip_type'        => $request->trip_type,
                'trip_id'          => $request->trip_id,
                'sms_sent'         => $request->sms_sent,
                'vehicle_no'       => $request->vehicle_no,
                'driver_name'      => $request->driver_name,
                'vehicle_category' => $request->vehicle_category,
                'vehicle_size'     => $request->vehicle_size,
                'product_details'  => $request->product_details,
                'driver_mobile'    => $request->driver_mobile,
                'challan'          => $request->challan,
                'driver_adv'       => $request->driver_adv,
                'remarks'          => $request->remarks,
                'food_cost'        => $request->food_cost,
                'total_exp'        => $request->total_exp,
                'total_rent'        => $request->total_rent,
                'c_adv'        => $request->c_adv,
                'c_due'        => $request->c_due,
                'vendor_rent'      => $request->vendor_rent,
                'advance'          => $request->advance,
                'challan_cost'     => $request->challan_cost,
                'fuel_cost'     => $request->fuel_cost,
                'c_address'     => $request->c_address,

                'odometer_start'     => $request->odometer_start,
                'odometer_end'     => $request->odometer_end,
                'run_km'     => $request->run_km,
                'req_fuel'     => $request->req_fuel,

                'cargo_weight'     => $request->cargo_weight,
                'cnf_bd'     => $request->cnf_bd,
                'cnf_ind'     => $request->cnf_ind,
                'tyer_maintaince'     => $request->tyer_maintaince,
                'scale_bill'     => $request->scale_bill,

                'd_day'     => $request->d_day,
                'd_amount'     => $request->d_amount,
                'd_total'     => $request->d_total,

                'v_d_day'     => $request->v_d_day,
                'v_d_total'     => $request->v_d_total,

                'due_amount'       => $request->due_amount,
                'parking_cost'     => $request->parking_cost,
                'night_guard'      => $request->night_guard,
                'toll_cost'        => $request->toll_cost,
                'feri_cost'        => $request->feri_cost,
                'police_cost'      => $request->police_cost,
                'chada'            => $request->chada,
                'labor'            => $request->labor,
                'others_cost'      => $request->others_cost,
                'vendor_name'      => $request->vendor_name,
                'additional_cost'  => $request->additional_cost,
                'created_by'  => $request->created_by,
                'chalan_rec'  => $request->chalan_rec,


                'helper_name'         => $request->helper_name,
                'status'           => $request->status ?? $trip->status,
            ]);

            // Update DriverLedger or VendorLedger
            if ($request->transport_type === "own_transport") {
                DriverLedger::updateOrCreate(
                    ['trip_id' => $trip->id], // condition
                    [
                        'user_id'          => Auth::id(),
                        'date'             => $request->start_date,
                        'driver_name'      => $request->driver_name,
                        'load_point'       => $request->load_point,
                        'unload_point'     => $request->unload_point,
                        'driver_commission' => $request->driver_commission,
                        'driver_adv'       => $request->driver_adv,
                        'parking_cost'     => $request->parking_cost,
                        'night_guard'      => $request->night_guard,
                        'toll_cost'        => $request->toll_cost,
                        'feri_cost'        => $request->feri_cost,
                        'police_cost'      => $request->police_cost,
                        'chada'            => $request->chada,
                        'challan_cost'     => $request->challan_cost,
                        'fuel_cost'     => $request->fuel_cost,
                        'others_cost'      => $request->others_cost,
                        'labor'            => $request->labor,

                        'cargo_weight'     => $request->cargo_weight,
                        'cnf_bd'     => $request->cnf_bd,
                        'cnf_ind'     => $request->cnf_ind,
                        'tyer_maintaince'     => $request->tyer_maintaince,
                        'scale_bill'     => $request->scale_bill,

                        'total_exp'        => $request->total_exp,
                    ]
                );
            } else {
                VendorLedger::updateOrCreate(
                    ['trip_id' => $trip->id], // condition
                    [
                        'user_id'     => Auth::id(),
                        'date'        => $request->start_date,
                        'driver_name' => $request->driver_name,
                        'load_point'  => $request->load_point,
                        'unload_point' => $request->unload_point,
                        'customer'    => $request->customer,
                        'vendor_name' => $request->vendor_name,
                        'vehicle_no'  => $request->vehicle_no,
                        'total_rent'   => $request->total_exp,
                        'advance'     => $request->advance,
                        'due_amount'  => $request->due_amount,
                        'v_d_day'     => $request->v_d_day,
                        'v_d_total'     => $request->v_d_total,



                    ]
                );
            }

            // Update OfficeLedger
            OfficeLedger::updateOrCreate(
                ['trip_id' => $trip->id],
                [
                    'user_id'     => Auth::id(),
                    'date'        => $request->start_date,
                    'unload_point' => $request->unload_point,
                    'load_point'  => $request->load_point,
                    'customer'    => $request->customer,
                    'remarks'          => $request->remarks,
                    'branch_name' => $request->branch_name,
                    'status'      => $request->status ?? "Pending",
                    'cash_out'    => $request->total_exp,
                    'cash_in'    => $request->c_adv,
                    'created_by'  => Auth::id(),
                ]
            );


            $lastLedger = FuelLedger::where('vehicle_no', $request->vehicle_no)
                ->orderBy('id', 'desc')
                ->first();

            $previousStock = $lastLedger ? $lastLedger->stock : 0;

            // 2️⃣ Calculate new stock after fuel usage
            $newStock = $previousStock - $request->req_fuel;
            FuelLedger::updateOrCreate(
                ['trip_id' => $trip->id],
                [
                    'user_id'    => Auth::id(),
                    'date'       => $request->date,
                    'trip_id'    => $trip->id,
                    'vehicle_no' => $request->vehicle_no,
                    'out_ltr'    => $request->req_fuel,
                    'stock'      => $newStock,
                ]
            );

            // Update CustomerLedger



            CustomerLedger::updateOrCreate(
                ['trip_id' => $trip->id],
                [

                    'user_id'     => Auth::id(),
                    'working_date'  => $request->start_date,  // fixed spelling
                    'customer_name' => $request->customer,
                    'trip_id'       => $trip->id,
                    'chalan'       => $request->challan,
                    'load_point'    => $request->load_point,
                    'unload_point'  => $request->unload_point,
                    'vehicle_no'    => $request->vehicle_no,
                    'bill_amount'   => $request->total_rent,
                    'c_adv'        => $request->c_adv,
                    'c_due'        => $request->c_due,
                    // fixed4
                    'd_day'     => $request->d_day,
                    'd_amount'     => $request->d_amount,
                    'd_total'     => $request->d_total,
                    'driver_name'   => $request->driver_name,
                    'chalan_rec'  => $request->chalan_rec,
                ]
            );



            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trip updated successfully',
                'data'    => $trip,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Find the trip belonging to the authenticated user
            $trip = Trip::find($id);

            if (!$trip) {
                return response()->json(['message' => 'Trip not found'], 404);
            }

            // Delete related entries
            DriverLedger::where('trip_id', $trip->id)->delete();
            VendorLedger::where('trip_id', $trip->id)->delete();
            OfficeLedger::where('trip_id', $trip->id)->delete();
            CustomerLedger::where('trip_id', $trip->id)->delete();
            FuelLedger::where('trip_id', $trip->id)->delete();

            // Delete the trip itself
            $trip->delete();

            DB::commit();

            return response()->json([
                'message' => 'Trip and related records deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete trip',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

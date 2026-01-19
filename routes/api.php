<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FuelController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\PartsController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\HelperController;
use App\Http\Controllers\Api\V1\OfficeController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PriceRateController;
use App\Http\Controllers\Api\V1\SalaryAdvController;
use App\Http\Controllers\Api\V1\AttendenceController;
use App\Http\Controllers\Api\V1\BonousController;
use App\Http\Controllers\Api\V1\MaintainceController;
use App\Http\Controllers\Api\V1\RentVehicleController;
use App\Http\Controllers\Api\V1\DailyExpenseController;
use App\Http\Controllers\Api\V1\DriverLedgerController;
use App\Http\Controllers\Api\V1\FundTransferController;
use App\Http\Controllers\Api\V1\OfficeLedgerController;
use App\Http\Controllers\Api\V1\VendorLedgerController;
use App\Http\Controllers\Api\V1\VendorPaymentController;
use App\Http\Controllers\Api\V1\CustomerLedgerController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\PaymentRecieveController;
use App\Http\Controllers\Api\V1\RequsitionController;
use App\Http\Controllers\Api\V1\SupplierLedgerController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SalarySheetController;
use App\Http\Controllers\Api\V1\InvertoyPurchaseController;
use App\Http\Controllers\Api\V1\SalesController;

Route::prefix('v1')->group(function () {

  // Public Routes (No auth needed)
  Route::post('/register', [AuthController::class, 'register']);
  Route::post('/login', [AuthController::class, 'login']);
  //  Route::get('/user', [UserController::class, 'index']);

  // Protected Routes (Need Sanctum auth)
  Route::middleware('auth:sanctum')->group(function () {

    // User info & logout
    //   Route::get('/users', [AuthController::class, 'index']);


    Route::apiResource('user', AuthController::class);
    // Route::get('/users', function () {
    //     return User::all();
    // });

    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
    Route::post('/logout', [AuthController::class, 'logout']);


    // daily expense
    Route::apiResource('expense', DailyExpenseController::class);

    // payments
    Route::apiResource('payments', PaymentController::class);

    // purchase
    Route::apiResource('purchase', PurchaseController::class);
    // 
    Route::post('purchase/{id}', [PurchaseController::class, 'update']);
    // parts route
    Route::apiResource('parts', PartsController::class);

    // mainataince
    Route::apiResource('maintaince', MaintainceController::class);

    // fuel 
    Route::apiResource('fuel', FuelController::class);
    
     Route::post('fuel/{id}', [FuelController::class, 'update']);

    // fuel ledger
    Route::get('/fuelLedger', [FuelController::class, 'stock']);

    // vehicle
    Route::apiResource('vehicle', VehicleController::class);

    // rent vehicle
    Route::apiResource('rentVehicle', RentVehicleController::class);

    // trip
    Route::apiResource('trip', TripController::class);

    // driver
    Route::apiResource('driver', DriverController::class);

    // helper
    Route::apiResource('helper', HelperController::class);

    // employee
    Route::apiResource('employee', EmployeeController::class);

    Route::post('employee/{id}', [EmployeeController::class, 'update']);


    // customer
    Route::apiResource('customer', CustomerController::class);

    // vendor
    Route::apiResource('vendor', VendorController::class);

    // vendor Payment
    Route::apiResource('vendor-payment', VendorPaymentController::class);
    Route::post('vendor-payment/{id}', [VendorPaymentController::class, 'update']);

    // payment recieve
    Route::apiResource('payment-recieve', PaymentRecieveController::class);
    Route::post('payment-recieve/{id}', [PaymentRecieveController::class, 'update']);
    // supplier
    Route::apiResource('supplier', SupplierController::class);


    // branch ledger
    Route::apiResource('OfficeLedger', OfficeLedgerController::class);



    //   suppliier ledger
    Route::apiResource('supplierLedger', SupplierLedgerController::class);

    //   Driver Ledger
    Route::apiResource('driverLedger', DriverLedgerController::class);

    //   Vendor Ledger
    Route::apiResource('vendorLedger', VendorLedgerController::class);

    //   Customer Ledger 
    Route::apiResource('customerLedger', CustomerLedgerController::class);


    //   salary advanced

    Route::apiResource('salaryAdvanced', SalaryAdvController::class);


    Route::apiResource('attendence', AttendenceController::class);


    // rate
    Route::apiResource('rate', PriceRateController::class);

    // office
    Route::apiResource('office', OfficeController::class);

    // fundtransfer
    Route::apiResource('fundTransfer', FundTransferController::class);

    // loan controller 
    Route::apiResource('loan', LoanController::class);

    // bonous
    Route::apiResource('bonous', BonousController::class);

    // requestion
    Route::apiResource('requestion', RequsitionController::class);




    // salary sheet
    Route::apiResource('salarySheet', SalarySheetController::class);



    Route::put('/salary-item/{id}', [SalarySheetController::class, 'updateSingle']);



    // invertory purchase
    Route::apiResource('inventroyPurchase', InvertoyPurchaseController::class);

    // sales
    Route::apiResource('sales', SalesController::class);


    Route::get('stock', [SalesController::class, 'stock']);
  });
});

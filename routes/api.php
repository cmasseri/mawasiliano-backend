<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\RankController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PersonnelExtraController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get(
    '/reports/rank-distribution',
    [ReportController::class, 'rankDistribution']
);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json([
        'message' => 'CSRF cookie set'
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']);

    Route::put(
        '/profile/change-password',
        [AuthController::class, 'changePassword']
    );

    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

    Route::get('/units/search', [UnitController::class, 'search']);

    Route::get(
        '/units/{id}/personnel',
        [PersonnelController::class, 'byUnit']
    );

    Route::get(
        '/units/{id}/personnelOps',
        [OperationController::class, 'personnelOps']
    );

    /*
    |--------------------------------------------------------------------------
    | Personnel
    |--------------------------------------------------------------------------
    */

    Route::get('/personnel/{id}', [PersonnelController::class, 'show']);

    Route::post('/personnel', [PersonnelController::class, 'store']);

    Route::put('/personnel/{id}', [PersonnelController::class, 'update']);

    Route::delete('/personnel/{id}', [PersonnelController::class, 'destroy']);

    Route::get(
        '/personnel-search/search',
        [PersonnelController::class, 'searchPersonnel']
    );

    /*
    |--------------------------------------------------------------------------
    | Reference Data
    |--------------------------------------------------------------------------
    */

    Route::get('/ranks', [RankController::class, 'index']);

    Route::get('/trades', [TradeController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Structures
    |--------------------------------------------------------------------------
    */

    Route::get('/structures', [StructureController::class, 'index']);

    Route::post('/structures', [StructureController::class, 'store']);

    Route::get('/structures/tree', [StructureController::class, 'tree']);

    Route::get(
        '/structures/{id}/next',
        [StructureController::class, 'getNextLevel']
    );

    /*
    |--------------------------------------------------------------------------
    | Appointments
    |--------------------------------------------------------------------------
    */

    Route::get('/appointments', [AppointmentController::class, 'index']);

    Route::get(
        '/appointments/unit/{unit_id}',
        [AppointmentController::class, 'byUnit']
    );

    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);

    Route::post('/appointments', [AppointmentController::class, 'store']);

    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);

    Route::delete(
        '/appointments/{id}',
        [AppointmentController::class, 'destroy']
    );

    /*
    |--------------------------------------------------------------------------
    | Personnel Extra Data
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/personnel/{id}/trades',
        [PersonnelExtraController::class, 'trades']
    );

    Route::get(
        '/personnel/{id}/education',
        [PersonnelExtraController::class, 'education']
    );

    Route::get(
        '/personnel/{id}/operations',
        [PersonnelExtraController::class, 'operations']
    );

    Route::get(
        '/personnel/{id}/trainings',
        [PersonnelExtraController::class, 'trainings']
    );

    Route::post(
        '/personnel-trades',
        [PersonnelExtraController::class, 'storeTrade']
    );

    Route::put(
        '/personnel-trades/{id}',
        [PersonnelExtraController::class, 'updateTrade']
    );

    Route::post(
        '/personnel-education',
        [PersonnelExtraController::class, 'storeEducation']
    );

    Route::put(
        '/personnel-education/{id}',
        [PersonnelExtraController::class, 'updateEducation']
    );

    Route::post(
        '/personnel-operations',
        [PersonnelExtraController::class, 'storeOperation']
    );

    Route::put(
        '/personnel-operations/{id}',
        [PersonnelExtraController::class, 'updateOperation']
    );

    Route::post(
        '/personnel-trainings',
        [PersonnelExtraController::class, 'storeTraining']
    );

    Route::put(
        '/personnel-trainings/{id}',
        [PersonnelExtraController::class, 'updateTraining']
    );

    /*
    |--------------------------------------------------------------------------
    | Operations
    |--------------------------------------------------------------------------
    */

    Route::get('/operations', [OperationController::class, 'index']);

    Route::get('/operations/{id}', [OperationController::class, 'show']);

    Route::post('/operations', [OperationController::class, 'store']);

    Route::put('/operations/{id}', [OperationController::class, 'update']);

    Route::post(
        '/personnel-operations/assign',
        [OperationController::class, 'assignPersonnel']
    );

    Route::get(
        '/operations/{id}/personnel',
        [OperationController::class, 'operationPersonnel']
    );

    /*
    |--------------------------------------------------------------------------
    | Transfers
    |--------------------------------------------------------------------------
    */

    Route::post('/transfers', [TransferController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:ADMINISTRATOR')->group(function () {

        Route::get('/users', [UserController::class, 'index']);

        Route::post('/users', [UserController::class, 'store']);

        Route::put('/users/{user}', [UserController::class, 'update']);

        Route::patch(
            '/users/{user}/toggle-status',
            [UserController::class, 'toggleStatus']
        );

    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get(
        '/dashboard/personnel-status',
        [DashboardController::class, 'personnelStatus']
    );

    Route::get(
        '/dashboard/operations-status',
        [DashboardController::class, 'operationsStatus']
    );

    Route::get(
        '/dashboard/personnel-by-unit',
        [DashboardController::class, 'personnelByUnit']
    );

    Route::get(
        '/dashboard/recent-activities',
        [DashboardController::class, 'recentActivities']
    );

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
 Route::get(
        '/reports/rank-distribution',
        [ReportController::class, 'rankDistribution']
    );

   Route::get('/dashboard/reports', [ReportController::class, 'dashboard']);


   Route::get(
    '/reports/trade-distribution',
    [ReportController::class, 'tradeDistribution']
);
Route::get('/reports/appointment-distribution', [ReportController::class, 'appointmentDistribution']);


Route::get(
    '/reports/gender-distribution',
    [ReportController::class, 'genderDistribution']
);




Route::get(
    '/reports/education-fields',
    [ReportController::class, 'educationFields']
);

Route::get(
    '/reports/personnel-by-education',
    [ReportController::class, 'personnelByEducation']
);




Route::get(
    '/personnel/{id}/retirement',
    [PersonnelController::class,'retirementInformation']
);

Route::get(
    '/reports/retirement', 
    [ReportController::class,'retirementReport'
]);

Route::patch(

    '/personnel/{id}/status',

    [PersonnelController::class,'updateStatus']

);

Route::get(
    '/personnel/{id}/promotions',
    [PersonnelExtraController::class, 'promotions']
);

Route::post(
    '/promotions',
    [PersonnelExtraController::class, 'storePromotion']
);

Route::get(
    '/personnel/{id}/next-rank',
    [PersonnelExtraController::class, 'nextRank']
);

Route::post(
    '/retirement-extensions',
    [PersonnelController::class, 'grantRetirementExtension']
);
Route::put(
    '/operations/{operation}/personnel/{personnel}/remove',
    [OperationController::class, 'removePersonnel']
);

});











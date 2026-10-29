<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Backward-compatible Sanctum user endpoint
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Role-based authorization probe routes (for verifying role enforcement)
Route::prefix('probe')->middleware('auth:sanctum')->group(function () {
    Route::get('/owner-only', function (Request $request) {
        return response()->json([
            'message' => 'Owner access granted',
            'role' => $request->user()->role,
        ]);
    })->middleware('role:owner');

    Route::get('/management', function (Request $request) {
        return response()->json([
            'message' => 'Management access granted',
            'role' => $request->user()->role,
        ]);
    })->middleware('role:owner,manager');

    Route::get('/staff', function (Request $request) {
        return response()->json([
            'message' => 'Staff access granted',
            'role' => $request->user()->role,
        ]);
    })->middleware('role:owner,manager,cashier');
});

// Items CRUD routes
Route::prefix('items')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ItemController::class, 'index'])->middleware('role:owner,manager,cashier');
    Route::post('/', [ItemController::class, 'store'])->middleware('role:owner,manager');
    Route::get('/{id}', [ItemController::class, 'show'])->middleware('role:owner,manager,cashier');
    Route::put('/{id}', [ItemController::class, 'update'])->middleware('role:owner,manager,cashier');
    Route::patch('/{id}', [ItemController::class, 'update'])->middleware('role:owner,manager,cashier');
    Route::delete('/{id}', [ItemController::class, 'destroy'])->middleware('role:owner');
});

// Customers CRUD routes
Route::prefix('customers')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->middleware('role:owner,manager,cashier');
    Route::post('/', [CustomerController::class, 'store'])->middleware('role:owner,manager,cashier');
    Route::get('/{id}', [CustomerController::class, 'show'])->middleware('role:owner,manager,cashier');
    Route::put('/{id}', [CustomerController::class, 'update'])->middleware('role:owner,manager,cashier');
    Route::patch('/{id}', [CustomerController::class, 'update'])->middleware('role:owner,manager,cashier');
    Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('role:owner');
});

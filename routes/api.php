<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldAccountController;
Route::prefix('v1')->group(function(){
 Route::post('/login',[FieldController::class,'login'])->middleware('throttle:8,1');
 Route::post('/login/verify-otp',[FieldController::class,'verifyTwoFactor'])->middleware('throttle:8,1');
 Route::middleware(['auth:sanctum','throttle:90,1'])->group(function(){
  Route::post('/logout',[FieldController::class,'logout']);
  Route::get('/account/profile',[FieldAccountController::class,'profile']);
  Route::post('/account/password',[FieldAccountController::class,'changePassword'])->middleware('throttle:5,1');
  Route::post('/account/2fa/setup',[FieldAccountController::class,'startTwoFactor'])->middleware('throttle:5,1');
  Route::post('/account/2fa/confirm',[FieldAccountController::class,'confirmTwoFactor'])->middleware('throttle:6,1');
  Route::post('/account/2fa/disable',[FieldAccountController::class,'disableTwoFactor'])->middleware('throttle:5,1');
  Route::get('/bootstrap',[FieldController::class,'bootstrap']);
  Route::get('/reports',[FieldController::class,'index']);
  Route::get('/reports/{case}',[FieldController::class,'show'])->whereNumber('case');
  Route::put('/reports/{case}',[\App\Http\Controllers\CaseRevisionController::class,'apiUpdate'])->whereNumber('case');
  Route::post('/reports',[FieldController::class,'store'])->middleware('throttle:20,1');
  Route::post('/reports/similar',[FieldController::class,'similar'])->middleware('throttle:30,1');
  Route::post('/reports/{case}/clarification',[FieldController::class,'clarification'])->whereNumber('case');
  Route::get('/evidence/{evidence}',[FieldController::class,'evidence'])->whereNumber('evidence');
 });
});

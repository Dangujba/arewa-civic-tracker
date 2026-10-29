<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FieldController;
Route::prefix('v1')->group(function(){
 Route::post('/login',[FieldController::class,'login'])->middleware('throttle:8,1');
 Route::post('/login/verify-otp',[FieldController::class,'verifyTwoFactor'])->middleware('throttle:8,1');
 Route::middleware(['auth:sanctum','throttle:90,1'])->group(function(){
  Route::post('/logout',[FieldController::class,'logout']);
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

<?php

use App\Http\Controllers\FilterController;
use App\Http\Controllers\ReadLaterController;
use App\Http\Controllers\User\DeliveryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\DailyController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\User\PasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
   return redirect()->route('home');
});

Route::view('cancelled', 'user.user.cancelled');
Route::get('@{username}', [UserController::class, 'show']);

Route::group(['middleware' => ['auth', 'verified']], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::post('feed/create', [FeedController::class, 'store'])->name('feed.create');

    Route::get('subscription/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscription.edit');
    Route::post('subscription/{subscription}/edit', [SubscriptionController::class, 'update']);
    Route::delete('subscription/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscription');

    Route::post('filter/{subscription}/create', [FilterController::class, 'store'])->name('filter.create');
    Route::post('filter/{filter}/edit', [FilterController::class, 'update'])->name('filter.edit');
    Route::delete('filter/{filter}', [FilterController::class, 'destroy'])->name('filter');

    Route::get('readlater', [ReadLaterController::class, 'index'])->name('readlater');
    Route::delete('readlater/{post}', [ReadLaterController::class, 'destroy'])->name('readlater.delete');

    Route::get('user', [UserController::class, 'edit'])->name('user.edit');
    Route::post('user', [UserController::class, 'update']);
    Route::get('user/cancel', [UserController::class, 'destroy'])->name('user.cancel')->middleware(['password.confirm']);
    Route::post('user/delivery', [DeliveryController::class, 'update'])->name('user.delivery');
    Route::post('user/password', [PasswordController::class, 'update'])->name('user.password');
});

Route::get('issue/{issue}', [IssueController::class, 'show'])->name('issue');
Route::get('link/{user}/{post}', [LinkController::class, 'show'])->name('link');

require __DIR__.'/auth.php';

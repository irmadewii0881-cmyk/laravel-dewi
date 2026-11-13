<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Controller Customer
use App\Http\Controllers\Api\Customer\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Api\Customer\LoginController as CustomerLoginController;
use App\Http\Controllers\Api\Customer\LogoutController as CustomerLogoutController;
use App\Http\Controllers\Api\Customer\MyOrderController as CustomerMyOrderController;
use App\Http\Controllers\Api\Customer\MyProfileController as CustomerMyProfileController;
use App\Http\Controllers\Api\Customer\RatingController as CustomerRatingController;

// Import Controller Public
use App\Http\Controllers\Api\Public\SliderController as PublicSliderController;
use App\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\ProductController as PublicProductController;
use App\Http\Controllers\Api\Public\CartController as PublicCartController;
use App\Http\Controllers\Api\Public\RajaOngkirController as PublicRajaOngkirController;
use App\Http\Controllers\Api\Public\CheckoutController as PublicCheckoutController;
use App\Http\Controllers\Api\Public\CallbackController as PublicCallbackController;

//route group customer
Route::group(['prefix' => 'customer'], function () {

    Route::post('/register', CustomerRegisterController::class)->name('customer.register');
    Route::post('/login', CustomerLoginController::class)->name('customer.login');
    Route::post('/logout', CustomerLogoutController::class)->name('customer.logout');
    Route::get('/my-orders', [CustomerMyOrderController::class, 'index'])->name('customer.my-orders');
    Route::get('/my-orders/{snap_token}', [CustomerMyOrderController::class, 'show'])->name('customer.my-orders.show');
    Route::get('/profile', [CustomerMyProfileController::class, 'index'])->name('customer.profile');
    Route::post('/profile', [CustomerMyProfileController::class, 'update'])->name('customer.profile.update');
    Route::post('/ratings', CustomerRatingController::class)->name('customer.ratings');

    /**
     * route 'checkout' (Dipindah ke sini agar terdeteksi sebagai user yang login)
     * @method "POST"
     */
    Route::post('/checkout', [PublicCheckoutController::class, 'store'])->name('customer.checkout');

});

//route group public
Route::group(['prefix' => 'public'], function () {

    Route::get('/sliders', PublicSliderController::class)->name('public.sliders');
    Route::get('/categories', [PublicCategoryController::class, 'index'])->name('public.categories');
    Route::get('/categories/{slug}', [PublicCategoryController::class, 'show'])->name('public.categories.show');
    Route::get('/products', [PublicProductController::class, 'index'])->name('public.products');
    Route::get('/products-popular', [PublicProductController::class, 'ProductPopular'])->name('public.products.popular');
    Route::get('/products/{slug}', [PublicProductController::class, 'show'])->name('public.products.show');
    Route::get('/carts', [PublicCartController::class, 'index'])->name('public.carts');
    Route::post('/carts', [PublicCartController::class, 'store'])->name('public.carts.store');
    Route::post('/carts/increment', [PublicCartController::class, 'IncrementCart'])->name('public.carts.increment');
    Route::post('/carts/decrement', [PublicCartController::class, 'DecrementCart'])->name('public.carts.decrement');
    Route::delete('/carts/{id}', [PublicCartController::class, 'destroy'])->name('public.carts.destroy');
    Route::get('/search-destination', [PublicRajaOngkirController::class, 'searchDestination'])->name('public.search-destination');
    Route::post('/check-ongkir', [PublicRajaOngkirController::class, 'checkOngkir'])->name('public.ongkir');

});

//route callback midtrans
Route::post('/callback', PublicCallbackController::class)->name('public.callback');
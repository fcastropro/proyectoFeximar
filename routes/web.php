<?php

use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\FarmProductAvailabilityController;
use App\Http\Controllers\Admin\FarmProductController;
use App\Http\Controllers\Admin\FarmProductPresentationController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PresentationBoxConfigController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ProfileController;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});*/
Route::get('/', function () {
    return view('index');
});
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Dashboard', [
                'farmsCount' => Farm::count(),
                'productsCount' => Product::count(),
                'buyersCount' => Buyer::count(),
                'ordersCount' => Order::count(),
            ]);
        })->name('dashboard');

        Route::resource('farms', FarmController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('farm-products', FarmProductController::class)->except(['show']);
        Route::resource('presentations', FarmProductPresentationController::class)->except(['show']);
        Route::resource('box-configs', PresentationBoxConfigController::class)
            ->except(['show'])
            ->parameters(['box-configs' => 'boxConfig']);
        Route::resource('availabilities', FarmProductAvailabilityController::class)->except(['show']);
        Route::resource('buyers', BuyerController::class)->except(['show']);
        Route::resource('orders', OrderController::class);

        Route::get('locations/provinces/{country}', [LocationController::class, 'provinces'])
            ->name('locations.provinces');
        Route::get('locations/cities/{province}', [LocationController::class, 'cities'])
            ->name('locations.cities');

        Route::get('catalog/varieties/{flowerType}', [CatalogController::class, 'varieties'])
            ->name('catalog.varieties');
    });
});

require __DIR__.'/auth.php';

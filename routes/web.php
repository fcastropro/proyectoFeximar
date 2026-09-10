<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BusinessIntelligenceController;
use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\CargoAgencyController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\FarmFinanceController;
use App\Http\Controllers\Admin\FarmProductAvailabilityController;
use App\Http\Controllers\Admin\FarmProductController;
use App\Http\Controllers\Admin\FarmProductPresentationController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PresentationBoxConfigController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VarietyController;
use App\Http\Controllers\Admin\Users\AdminAccountController;
use App\Http\Controllers\Admin\Users\BuyerAccountController;
use App\Http\Controllers\Admin\Users\FarmAccountController;
use App\Http\Controllers\Buyer\CartController as BuyerCartController;
use App\Http\Controllers\Buyer\CatalogController as BuyerCatalogController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ProfileController as BuyerProfileController;
use App\Http\Controllers\Farm\AvailabilityController as FarmAvailabilityController;
use App\Http\Controllers\Farm\BusinessIntelligenceController as FarmBusinessIntelligenceController;
use App\Http\Controllers\Farm\DashboardController as FarmDashboardController;
use App\Http\Controllers\Farm\OrderController as FarmOrderController;
use App\Http\Controllers\Farm\PaymentController as FarmPaymentController;
use App\Http\Controllers\Farm\ProductController as FarmProductPortalController;
use App\Http\Controllers\Farm\ProfileController as FarmProfileController;
use App\Http\Controllers\AIAdvisorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('index');
});

Route::post('/ai/advisor/query', [AIAdvisorController::class, 'query'])
    ->middleware('throttle:60,1')
    ->name('ai.advisor.query');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('farms', FarmController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('varieties', VarietyController::class)->except(['show']);
        Route::resource('farm-products', FarmProductController::class)->except(['show']);
        Route::resource('presentations', FarmProductPresentationController::class)->except(['show']);
        Route::resource('box-configs', PresentationBoxConfigController::class)
            ->except(['show'])
            ->parameters(['box-configs' => 'boxConfig']);
        Route::resource('availabilities', FarmProductAvailabilityController::class)->except(['show']);
        Route::resource('buyers', BuyerController::class)->except(['show']);
        Route::resource('orders', OrderController::class);
        Route::resource('cargo-agencies', CargoAgencyController::class)->except(['show']);

        Route::prefix('users')->name('users.')->group(function () {
            Route::prefix('admins')->name('admins.')->group(function () {
                Route::get('/', [AdminAccountController::class, 'index'])->name('index');
                Route::get('/create', [AdminAccountController::class, 'create'])->name('create');
                Route::post('/', [AdminAccountController::class, 'store'])->name('store');
                Route::get('/{user}', [AdminAccountController::class, 'show'])->name('show');
                Route::get('/{user}/edit', [AdminAccountController::class, 'edit'])->name('edit');
                Route::put('/{user}', [AdminAccountController::class, 'update'])->name('update');
                Route::post('/{user}/toggle-active', [AdminAccountController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{user}/reset-password', [AdminAccountController::class, 'resetPassword'])->name('reset-password');
            });

            Route::prefix('farms')->name('farms.')->group(function () {
                Route::get('/', [FarmAccountController::class, 'index'])->name('index');
                Route::get('/create', [FarmAccountController::class, 'create'])->name('create');
                Route::post('/', [FarmAccountController::class, 'store'])->name('store');
                Route::get('/{farmUser}', [FarmAccountController::class, 'show'])->name('show');
                Route::get('/{farmUser}/edit', [FarmAccountController::class, 'edit'])->name('edit');
                Route::put('/{farmUser}', [FarmAccountController::class, 'update'])->name('update');
                Route::delete('/{farmUser}', [FarmAccountController::class, 'destroy'])->name('destroy');
                Route::post('/{farmUser}/toggle-active', [FarmAccountController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{farmUser}/reset-password', [FarmAccountController::class, 'resetPassword'])->name('reset-password');
            });

            Route::prefix('buyers')->name('buyers.')->group(function () {
                Route::get('/', [BuyerAccountController::class, 'index'])->name('index');
                Route::get('/create', [BuyerAccountController::class, 'create'])->name('create');
                Route::post('/', [BuyerAccountController::class, 'store'])->name('store');
                Route::get('/{buyerUser}', [BuyerAccountController::class, 'show'])->name('show');
                Route::get('/{buyerUser}/edit', [BuyerAccountController::class, 'edit'])->name('edit');
                Route::put('/{buyerUser}', [BuyerAccountController::class, 'update'])->name('update');
                Route::delete('/{buyerUser}', [BuyerAccountController::class, 'destroy'])->name('destroy');
                Route::post('/{buyerUser}/toggle-active', [BuyerAccountController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{buyerUser}/reset-password', [BuyerAccountController::class, 'resetPassword'])->name('reset-password');
            });
        });

        Route::redirect('farm-users', '/admin/users/farms')->name('farm-users.index');
        Route::redirect('farm-users/create', '/admin/users/farms/create')->name('farm-users.create');
        Route::redirect('buyer-users', '/admin/users/buyers')->name('buyer-users.index');
        Route::redirect('buyer-users/create', '/admin/users/buyers/create')->name('buyer-users.create');
        Route::redirect('users', '/admin/users/admins')->name('users.index');

        Route::get('farm-finances', [FarmFinanceController::class, 'index'])->name('farm-finances.index');
        Route::get('farm-finances/create', [FarmFinanceController::class, 'create'])->name('farm-finances.create');
        Route::post('farm-finances', [FarmFinanceController::class, 'store'])->name('farm-finances.store');
        Route::get('farm-finances/order/{order}/options', [FarmFinanceController::class, 'farmOptions'])
            ->name('farm-finances.order-options');
        Route::get('farm-finances/{farmFinance}', [FarmFinanceController::class, 'show'])->name('farm-finances.show');
        Route::post('farm-finances/{farmFinance}/payments', [FarmFinanceController::class, 'storePayment'])
            ->name('farm-finances.payments.store');

        Route::get('locations/provinces/{country}', [LocationController::class, 'provinces'])
            ->name('locations.provinces');
        Route::get('locations/cities/{province}', [LocationController::class, 'cities'])
            ->name('locations.cities');

        Route::get('catalog/varieties/{flowerType}', [CatalogController::class, 'varieties'])
            ->name('catalog.varieties');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('orders/{order}/pdf', [ReportController::class, 'order'])->name('orders.pdf');
            Route::get('farms/pdf', [ReportController::class, 'farms'])->name('farms.pdf');
            Route::get('farms/{farm}/pdf', [ReportController::class, 'farm'])->name('farms.show-pdf');
            Route::get('availability/pdf', [ReportController::class, 'weeklyAvailability'])->name('availability.pdf');
            Route::get('buyers/pdf', [ReportController::class, 'buyers'])->name('buyers.pdf');
            Route::get('farm-payables/pdf', [ReportController::class, 'farmPayables'])->name('farm-payables.pdf');
            Route::get('buyer-sales/pdf', [ReportController::class, 'buyerSales'])->name('buyer-sales.pdf');
        });

        Route::prefix('exports')->name('exports.')->group(function () {
            Route::get('/', fn () => Inertia::render('Admin/Exports/Index'))->name('index');
            Route::get('farms', [ExportController::class, 'farms'])->name('farms');
            Route::get('buyers', [ExportController::class, 'buyers'])->name('buyers');
            Route::get('orders', [ExportController::class, 'orders'])->name('orders');
            Route::get('availabilities', [ExportController::class, 'availabilities'])->name('availabilities');
            Route::get('products', [ExportController::class, 'products'])->name('products');
            Route::get('sales', [ExportController::class, 'sales'])->name('sales');
            Route::get('payments', [ExportController::class, 'payments'])->name('payments');
        });

        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

        Route::prefix('bi')->name('bi.')->group(function () {
            Route::get('/', [BusinessIntelligenceController::class, 'executive'])->name('executive');
            Route::get('/sales', [BusinessIntelligenceController::class, 'sales'])->name('sales');
            Route::get('/trends', [BusinessIntelligenceController::class, 'trends'])->name('trends');
            Route::get('/finance', [BusinessIntelligenceController::class, 'finance'])->name('finance');
            Route::get('/operations', [BusinessIntelligenceController::class, 'operations'])->name('operations');
            Route::get('/prediction', [BusinessIntelligenceController::class, 'prediction'])->name('prediction');
            Route::get('/prediction-dataset', [BusinessIntelligenceController::class, 'predictionDataset'])
                ->name('prediction-dataset');
        });
    });

    Route::prefix('farm')->name('farm.')->middleware('farm')->group(function () {
        Route::get('/dashboard', FarmDashboardController::class)->name('dashboard');
        Route::get('/profile', [FarmProfileController::class, 'show'])->name('profile');
        Route::get('/products', [FarmProductPortalController::class, 'index'])->name('products.index');

        Route::resource('availabilities', FarmAvailabilityController::class)->except(['show']);

        Route::get('/orders', [FarmOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{fulfillment}', [FarmOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{fulfillment}/transition', [FarmOrderController::class, 'transition'])
            ->name('orders.transition');

        Route::get('/payments', [FarmPaymentController::class, 'index'])->name('payments.index');

        Route::prefix('bi')->name('bi.')->group(function () {
            Route::get('/', [FarmBusinessIntelligenceController::class, 'executive'])->name('executive');
            Route::get('/sales', [FarmBusinessIntelligenceController::class, 'sales'])->name('sales');
            Route::get('/trends', [FarmBusinessIntelligenceController::class, 'trends'])->name('trends');
            Route::get('/prediction-dataset', [FarmBusinessIntelligenceController::class, 'predictionDataset'])
                ->name('prediction-dataset');
        });
    });

    Route::prefix('buyer')->name('buyer.')->middleware('buyer')->group(function () {
        Route::get('/dashboard', BuyerDashboardController::class)->name('dashboard');
        Route::get('/catalog', [BuyerCatalogController::class, 'index'])->name('catalog.index');
        Route::get('/cart', [BuyerCartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [BuyerCartController::class, 'store'])->name('cart.store');
        Route::put('/cart/items/{item}', [BuyerCartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [BuyerCartController::class, 'destroy'])->name('cart.items.destroy');
        Route::delete('/cart', [BuyerCartController::class, 'clear'])->name('cart.clear');
        Route::get('/checkout', [BuyerCartController::class, 'checkoutForm'])->name('checkout');
        Route::post('/checkout', [BuyerCartController::class, 'checkout'])->name('checkout.store');
        Route::get('/orders', [BuyerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}/pdf', [BuyerOrderController::class, 'pdf'])->name('orders.pdf');
        Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
        Route::get('/profile', [BuyerProfileController::class, 'show'])->name('profile');
    });
});

require __DIR__.'/auth.php';

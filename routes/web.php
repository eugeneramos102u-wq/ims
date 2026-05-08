<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// ── Auth ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ── Authenticated app ──
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Stock Adjustments
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/stock-adjustments-create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
        Route::get('/stock-adjustments/{stockAdjustment}/edit', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
        Route::put('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'update'])->name('stock-adjustments.update');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post');
        Route::post('/stock-adjustments/{stockAdjustment}/void', [StockAdjustmentController::class, 'void'])->name('stock-adjustments.void');
    });
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');

    // Items
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
    });

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

    // Purchase Orders
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/purchase-orders-create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/purchase-orders/{purchaseOrder}/issue', [PurchaseOrderController::class, 'issue'])->name('purchase-orders.issue');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    // Stock Receiving (Stock IN against a PO)
    Route::get('/stock-receiving', [GoodsReceiptController::class, 'index'])->name('stock-receiving.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/purchase-orders/{purchaseOrder}/receive', [GoodsReceiptController::class, 'createFromPO'])->name('stock-receiving.create-from-po');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [GoodsReceiptController::class, 'storeFromPO'])->name('stock-receiving.store-from-po');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/stock-receiving/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm'])->name('stock-receiving.confirm');
        Route::post('/stock-receiving/{goodsReceipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('stock-receiving.cancel');
    });
    Route::get('/stock-receiving/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('stock-receiving.show');

    // Sales Orders
    Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/sales-orders-create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::get('/sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
        Route::put('/sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
        Route::post('/sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
    });
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');

    // Invoices
    Route::get('/invoices', [SalesInvoiceController::class, 'index'])->name('invoices.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/sales-orders/{salesOrder}/invoice', [SalesInvoiceController::class, 'createFromSO'])->name('invoices.create-from-so');
        Route::post('/sales-orders/{salesOrder}/invoice', [SalesInvoiceController::class, 'storeFromSO'])->name('invoices.store-from-so');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/invoices/{invoice}/issue', [SalesInvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('/invoices/{invoice}/void', [SalesInvoiceController::class, 'void'])->name('invoices.void');
    });
    Route::get('/invoices/{invoice}', [SalesInvoiceController::class, 'show'])->name('invoices.show');

    // Collections
    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/invoices/{invoice}/payment', [CollectionController::class, 'create'])->name('collections.create');
        Route::post('/invoices/{invoice}/payment', [CollectionController::class, 'store'])->name('collections.store');
    });

    // Bank Accounts (master data)
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])->name('bank-accounts.create');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::get('/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    });

    // Deposits
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::middleware('role:Admin,Manager,Cashier')->group(function () {
        Route::get('/deposits-create', [DepositController::class, 'create'])->name('deposits.create');
        Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    });
    Route::middleware('role:Admin,Manager')->group(function () {
        Route::post('/deposits/{deposit}/post', [DepositController::class, 'post'])->name('deposits.post');
        Route::post('/deposits/{deposit}/cancel', [DepositController::class, 'cancel'])->name('deposits.cancel');
    });
    Route::get('/deposits/{deposit}', [DepositController::class, 'show'])->name('deposits.show');
});

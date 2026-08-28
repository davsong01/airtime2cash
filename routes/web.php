<?php

use App\Http\Controllers\Admin\AutoSyncOperationsController;
use App\Http\Controllers\Admin\CallbackOperationsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Airtime2CashController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\APIController;
use App\Http\Controllers\AutoSyncAirtimeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\BillerLogController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BlackListController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerAnnouncementController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerLevelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailApiController;
use App\Http\Controllers\EmailLogController;
use App\Http\Controllers\KycDataController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Providers\AutoSyncController;
use App\Http\Controllers\ReservedAccountController;
use App\Http\Controllers\ReservedAccountNumberController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VariationController;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Provider webhooks
Route::post('log-provider-webhook/{provider_id}', [CallbackOperationsController::class, 'logWebhook'])->name('log.provider.webhook');
Route::get('cron/analyze-webhook/{pick}', [CallbackOperationsController::class, 'analyzeProviderCallbackResponse'])->name('callback.provider.analyze');
Route::get('cron/requery-pending-transactions/{api?}/{pick?}', [TransactionController::class, 'requeryPendingTransactionsByApi'])->name('admin.requery.pending.transactions.by.api');

// End provider webhooks

// Payment provider webhooks
Route::post('log-p-callback/{provider}', [PaymentController::class, 'dumpCallback'])->name('log.payment.response');
Route::get('cron/analyze-callback', [PaymentController::class, 'analyzeCallbackResponse'])->name('callback.analyze');
Route::get('cron/api-availability-monitor/{windowMinutes?}/{sampleSize?}', [APIController::class, 'monitorAvailability'])->name('cron.api-availability-monitor');
// End Payment provider webhooks
Route::get('cron/sendemails', [Controller::class, 'cronSendEmails']);
Route::get('generate-api-keys', function(){
    $users = User::all();

    foreach($users as $user){
        if(empty($user->api_key)){
            $user->update([
                'api_key' => strrev(md5($user->username))
            ]);
        }
    }
});
Route::middleware(['auth', 'verified','ipcheck'])->group(function () {
    Route::get('/create-transaction-pin', [DashboardController::class, 'createTransactionPin'])->name('customer.create.pin');
    Route::post('/create-transaction-pin', [DashboardController::class, 'processCreateTransactionPin'])->name('customer.process.create.pin');
});

Route::middleware(['auth', 'verified', 'tpin', 'ipcheck'])->group(function () {
    Route::middleware('reserved_account')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        // Route::get('/dashboard', [DashboardController::class, 'index'])->name('customer.dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    Route::get('/reset-transaction-pin', [DashboardController::class, 'resetTransactionPin'])->name('customer.reset.pin');
    Route::post('/process-transaction-pin-reset', [DashboardController::class, 'processResetTransactionPin'])->name('process.transaction.pin.reset');
    Route::get('confirm_reset_pin', [DashboardController::class, 'resetPin2']);
    Route::post('reset_pin_final', [DashboardController::class, 'finalProcessPin'])->name('final.pin.reset');
    // Route::post('change-pin', [HomeController::class, 'processResetPin'])->name('pin.process.reset');
    Route::get('airtime-to-cash', [TransactionController::class, 'airtimeToCash'])->name('airtime-to-cash');
    Route::get('wallet-to-bank/{sluhg}', [TransactionController::class, 'walletToBank'])->name('wallet-to-bank');

    Route::get('customer-get-variations/{product}', [VariationController::class, 'getCustomerVariations'])->name('get.customer.variations');

    Route::middleware(['kyc'])->group(function () {
        Route::get('customer/{slug}', [TransactionController::class, 'showProductsPage'])->name('open.transaction.page');
        Route::post('customer-initialize-transaction', [TransactionController::class, 'initializeTransaction'])->name('initialize.transaction');
        Route::post('customer-initialize-airtime2cash-transaction', [TransactionController::class, 'initializeAirtime2CashTransaction'])->name('initialize.airtime2cashtransaction');
        Route::post('airtime-to-cash/auto/initiate', [TransactionController::class, 'initializeAirtime2CashTransaction'])->middleware('throttle:5,1')->name('airtime2cash.auto.initiate');
        Route::post('airtime-to-cash/auto/complete', [TransactionController::class, 'processAirtime2CashTransaction'])->middleware('throttle:10,1')->name('airtime2cash.auto.complete');
        Route::post('airtime-to-cash/auto/resend-otp', [TransactionController::class, 'resendOtp'])->middleware('throttle:3,1')->name('airtime2cash.auto.resend-otp');
        Route::post('customer-initialize-wallet2banktransaction/{product}', [TransactionController::class, 'initializeWalletToBankTransaction'])->name('initialize.wallet2banktransaction');


        Route::get('customer-transactions', [TransactionController::class, 'customerTransactionHistory'])->name('customer.transaction.history');
        Route::get('customer-a2c-transactions', [TransactionController::class, 'customerAirtime2CashTransactionHistory'])->name('customer.airtime2cash.transaction.history');

        Route::post('customer-verify', [TransactionController::class, 'verify'])->name('verify.unique.element');
        Route::get('customer-transaction_status/{transaction_id}', [TransactionController::class, 'transactionStatus'])->name('transaction.status');
        Route::get('customer-airtime-to-cash-transaction_status/{transaction_id}', [TransactionController::class, 'Airtime2CashTransactionStatus'])->name('airtime2cash.transaction.status');
        Route::post('verify-bank-details', [TransactionController::class, 'verifyBankDetails'])->name('customer.verify.bank.details');

        Route::get('customer-transaction-report', [TransactionController::class, 'showTransactionReportPage'])->name('customer.transaction.report');
        Route::get('customer-load-wallet', [DashboardController::class, 'showLoadWalletPge'])->name('customer.load.wallet');
        Route::get('customer-level-upgrade', [DashboardController::class, 'showUpgradeForm'])->name('customer.level.upgrade');
        Route::post('process-customer-load-wallet', [PaymentController::class, 'redirectToUrl'])->name('process-customer-load-wallet');
        Route::post('level-upgrade', [DashboardController::class, 'upgradeAccount'])->name('customer.level.upgrade.process');
        Route::get('download-transaction-receipt/{transaction_id}', [TransactionController::class, 'transactionReceipt'])->name('transaction.receipt.download');
        Route::get('download-airtime2cash.transaction-receipt/{transaction_id}', [TransactionController::class, 'airtime2CashTransactionReceipt'])->name('airtime2cash.transaction.receipt.download');

        Route::get('downlines/process/withdrawal', [DashboardController::class, 'downlinesWithdrawal'])->name('downlines.withdraw');
        Route::post('downlines/withdraw', [DashboardController::class, 'processWithdrawal'])->name('process.withdrawal');
        Route::get('downlines/{id?}', [DashboardController::class, 'downlines'])->name('downlines');
        Route::get('alldownlines', [DashboardController::class, 'allDownlines'])->name('alldownlines');
        Route::get('api-settings', [DashboardController::class, 'apiSettings'])->name('api.settings');

    });
    Route::get('payment-callback/{provider_id?}', [PaymentController::class, 'analyzePaymentResponse'])->name('payment-callback');
    Route::get('customer-update-kyc-info', [DashboardController::class, 'updateKycInfo'])->name('update.kyc.details');
    Route::post('customer-update-kyc-info', [DashboardController::class, 'processUpdateKycInfo'])->name('update.kyc.details.process');
    Route::get('get-lga-by-statename/{state}', [KycDataController::class, 'getLgaByStateName'])->name('kyc-get-lga-by-state');
    Route::post('customer-get-discount', [TransactionController::class, 'getCustomerDiscount'])->name('get.customer.discount');

    // Route::post('transaction-confirm/{provider}/{reference?}', [PaymentController::class, 'logPaymentResponse'])->name('log.payment.response');
});

Route::middleware('auth')->group(function () {
    Route::get('/announcements', [CustomerAnnouncementController::class, 'index'])->name('customer.announcements.index');
    Route::get('/notifications', [CustomerAnnouncementController::class, 'notifications'])->name('customer.notifications.index');
    Route::post('/notifications/read-all', [CustomerAnnouncementController::class, 'markAllAsRead'])->name('customer.notifications.read-all');
    Route::post('/notifications/{notification}/read', [CustomerAnnouncementController::class, 'markAsRead'])->name('customer.notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/get-keys', [ProfileController::class, 'generateKeys'])->name('profile.keys');
});

// Admin
Route::middleware(['auth', 'verified', 'admin', 'ipcheck', 'adminRoute'])->prefix('admin')->group(function () {
    Route::resource('product', ProductController::class);
    Route::resource('airtime2cash', Airtime2CashController::class);

    Route::get('pull-product', [ProductController::class, 'pullProducts'])->name('product.pull');
    Route::get('repull-product', [ProductController::class, 'pullProducts'])->name('product.repull');
    Route::post('product/bulk-delete', [ProductController::class, 'bulkDelete'])->name('product.bulk-delete');

    Route::get('duplicate-product/{product}', [ProductController::class, 'duplicateProduct'])->name('duplicate.product');
    Route::resource('api', APIController::class);
    Route::get('api-balance/{api}', [APIController::class, 'getBalance'])->name('api.balance');
    Route::post('api/{api}/pull-banks', [APIController::class, 'pullBanks'])->name('api.pull.banks');
    Route::resource('banks', BankController::class);

    Route::resource('category', CategoryController::class);
    Route::get('pull-categories', [CategoryController::class, 'pullCategories'])->name('category.pull');
    Route::get('repull-categories', [CategoryController::class, 'pullCategories'])->name('category.repull');

    Route::resource('customer-blacklist', BlackListController::class);
    Route::resource('announcement', AnnouncementController::class);
    Route::get('emails/send/{count?}', [EmailLogController::class, 'sendMail'])->name('emails.send');
    Route::get('emails/fire-mail/{log}', [EmailLogController::class, 'send'])->name('emails-send');
    Route::get('emails/pending', [EmailLogController::class, 'pending'])->name('emails.pending');
    Route::get('emails/resend/{id}', [EmailLogController::class, 'resend'])->name('emails.resend');
    Route::patch('emails/update/{id}', [EmailLogController::class, 'update'])->name('emails.update');
    Route::get('emails/destroy/{id}', [EmailLogController::class, 'destroy'])->name('emails.destroy');
    Route::get('emails/clear', [EmailLogController::class, 'sweep'])->name('emails.sweep');
    Route::get('emails', [EmailLogController::class, 'index'])->name('emails.index');
    Route::get('setup-emails', [EmailApiController::class, 'edit'])->name('email.setup');
    Route::post('setup-emails', [EmailApiController::class, 'update'])->name('email.setup.update');

    // transactions route
    Route::get('transactions', [TransactionController::class, 'transView'])->name('admin.trans');
    Route::get('wallet-transactions', [TransactionController::class, 'walletTransView'])->name('admin.walletlog');
    Route::get('admin-wallet-funding-log', [TransactionController::class, 'walletFundingLogView'])->name('admin.walletfundinglog');
    Route::get('admin-airtime-2-cash-log', [TransactionController::class, 'airtimeToCashTransactions'])->name('admin.airtime.2.cash.log');
    Route::get('callback-operations', [CallbackOperationsController::class, 'index'])->name('admin.autosync.index');
    Route::get('operations/webhooks', [CallbackOperationsController::class, 'webhooks'])->name('admin.webhooks.index');
    Route::get('operations/api-request-logs', [CallbackOperationsController::class, 'apiLogs'])->name('admin.api-logs.index');
    Route::get('clear-api-request-log', [CallbackOperationsController::class, 'clearApiRequestLogs'])->name('admin.autosync.api-request.clear');
    Route::get('admin.webhook-log', [CallbackOperationsController::class, 'clearWebhookLogs'])->name('admin.api-request.clear');

    Route::post('webhooks/{webhook}/resolve', [CallbackOperationsController::class, 'resolve'])->name('admin.autosync.webhooks.resolve');
    Route::post('operations/webhooks/bulk-delete', [CallbackOperationsController::class, 'bulkDeleteWebhooks'])->name('admin.webhooks.bulk-delete');
    Route::post('operations/webhooks/bulk-revert', [CallbackOperationsController::class, 'bulkRevertWebhooks'])->name('admin.webhooks.bulk-revert');

    Route::get('admin-earninglog', [TransactionController::class, 'walletEarningView'])->name('admin.earninglog');
    Route::get('credit-customer', [TransactionController::class, 'creditCustomerPage'])->name('admin.credit.customer');
    Route::get('debit-customer', [TransactionController::class, 'debitCustomerPage'])->name('admin.debit.customer');
    Route::post('process-credit-debit', [TransactionController::class, 'processCreditDebit'])->name('admin.process.credit.debit');
    Route::get('admin-kyc', [KycDataController::class, 'adminKycIndex'])->name('admin.kyc');
    Route::get('admin-kyc/customer-suggestions', [KycDataController::class, 'customerSuggestions'])
        ->middleware('throttle:120,1')
        ->name('admin.kyc.customer-suggestions');
    Route::get('admin-reserved-account', [ReservedAccountNumberController::class, 'index'])->name('admin.reserved.accounts');
    Route::post('admin-reserved-account/sync-provider-ids', [ReservedAccountNumberController::class, 'syncProviderIds'])->name('admin.reserved.accounts.sync-provider-ids');
    Route::get('account-transactions/{account}', [ReservedAccountNumberController::class, 'show'])->name('account.transactions');
    Route::get('admin-callback-analysis', [PaymentController::class, 'callBackAnalysis'])->name('callback.analysis');
    Route::get('admin-callback-analysis-reset/{callback}', [PaymentController::class, 'resetCallBackResponse'])->name('callback.reset');


    Route::get('reserved-account-delete/{account}', [ReservedAccountNumberController::class, 'delete'])->name('reserved_account.delete');

    Route::get('single-transaction-view/{transaction}', [TransactionController::class, 'singleTransactionView'])->name('admin.single.transaction.view');
    Route::post('single-transaction-view/{transaction}/resolve', [TransactionController::class, 'resolvePendingTransactionAction'])->name('admin.single.transaction.resolve');
    Route::get('single-airtime2cash-transaction-view/{transaction}', [TransactionController::class, 'singleAirtimeTransactionView'])->name('admin.single.airtime2cash.transaction.view');
    Route::get('single-airtime2cash-transaction-view/{transaction}/requery', [TransactionController::class, 'requeryAirtimeTransaction'])->name('admin.requery.airtime2cash.transaction');
    Route::get('query-wallet/{transactionlog?}', [TransactionController::class, 'queryWallet'])->name('admin.query.wallet');
    Route::get('requery-transaction/{transactionlog?}', [TransactionController::class, 'requery'])->name('admin.requery.transaction');
    Route::get('requery-callback-analysis/{reference}', [TransactionController::class, 'requeryCallback'])->name('admin.requery.callback');

    Route::post('change-transaction-method/{transaction}', [TransactionController::class, 'changeTransactionMethod'])->name('admin.changetransactionmethod');
    Route::get('approve-airtime2cash-transaction/{transaction}', [TransactionController::class, 'approveAirtime2CashTransactions'])->name('admin.approve.airtime2cash.transaction');
    Route::post('decline-airtime2cash-transaction/{transaction}', [TransactionController::class, 'declineAirtime2CashTransactions'])->name('admin.decline.airtime2cash.transaction');

    Route::post('verify-bank-details', [TransactionController::class, 'verifyBankDetails'])->name('admin.verify.bank.details');

    Route::get('customers/{status?}', [CustomerController::class, 'customers'])->name('customers');
    Route::get('customers-active/{status}', [CustomerController::class, 'customers'])->name('customers.active');
    Route::get('customers-suspended/{status}', [CustomerController::class, 'customers'])->name('customers.suspended');
    Route::get('customer/edit/{id}', [CustomerController::class, 'singleCustomer'])->name('customers.edit');
    Route::post('customer/update/{id}', [CustomerController::class, 'updateCustomer'])->name('customers.update');
    Route::post('customers/bulk-actions', [CustomerController::class, 'bulkActions'])->name('customers.bulk-actions');
    Route::resource('customerlevel', CustomerLevelController::class);
    Route::get('customers-unverified', [CustomerController::class, 'unverifiedCustomers'])->name('customers.unverified');
    Route::get('customers-verify/{customer}', [CustomerController::class, 'verifyCustomer'])->name('customer.verify');
    Route::get('customer-delete/{customer}', [CustomerController::class, 'deleteCustomer'])->name('customer.delete');
    Route::post('verify-actions', [CustomerController::class, 'verifyMultiActions'])->name('verify-users-actions');


    Route::get('pull-variations/{product}', [VariationController::class, 'pullVariations'])->name('variations.pull');
    Route::post('update-variations/{product}', [VariationController::class, 'updateVariations'])->name('variations.update');
    Route::post('manual-variations-add/{product}', [VariationController::class, 'addManualVariations'])->name('manual.variations.add');
    Route::get('delete-variations/{variation}', [VariationController::class, 'deleteVariations'])->name('variation.delete');

    Route::post('create-reserved-account/{customer}', [CustomerController::class, 'addReservedAccounts'])->name('create.reserved.account');

    Route::controller(AdminController::class)->group(function () {
        Route::get('admins', 'index')->name('admins');
        Route::get('admin/new', 'create')->name('newAdmin');
        Route::post('admin/save', 'store')->name('adminSave');
        Route::get('admin/view', 'view')->name('viewAdmin');
        Route::post('admin/update', 'update')->name('updateAdmin');
        Route::get('verify-biller', 'verifyBiller')->name('admin.verifybiller');
        Route::post('verify-post', 'verifyPost')->name('admin.verify.post');
    });

    Route::resource('billerlog', BillerLogController::class);
    Route::resource('role', RoleController::class);
    Route::resource('permission', RolePermissionController::class);

    Route::get('settings-update', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('settings-update', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('verify-transaction/{reference}/{provider_id?}', [PaymentController::class, 'verifyPayment'])->name('transaction.verify');

    Route::post('transaction-pin-reset/{user}', [CustomerController::class, 'resetTransactionPin'])->name('admin.transaction.pin.reset');
    Route::post('password-reset/{user}', [CustomerController::class, 'resetPassword'])->name('admin.password.reset');
    Route::post('customer-update-kyc/{customer}', [CustomerController::class, 'processCustomerUpdateKycInfo'])->name('admin.customer.update.kyc');
    Route::get('customer-approve-kyc/{customer}', [CustomerController::class, 'approveCustomerKyc'])->name('admin.customer.approve.kyc');
    Route::post('customer-decline-kyc/{customer}', [CustomerController::class, 'declineCustomerKyc'])->name('admin.customer.decline.kyc');
    Route::get('/dashboard-widgets/{type}', [DashboardController::class, 'dashboardWidgets']);
});

require __DIR__ . '/auth.php';

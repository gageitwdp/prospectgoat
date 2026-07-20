<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\GlobalAccountOversightController;
use App\Http\Controllers\Admin\PlanModuleVisibilityController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Admin\LeadImportController;
use App\Http\Controllers\Admin\ProspectingController;
use App\Http\Controllers\Admin\ProspectingScriptController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LeadIntakeController;
use App\Http\Controllers\MortgageCalculatorController;
use App\Http\Controllers\Manager\LeadActivityController;
use App\Http\Controllers\Manager\LeadController;
use App\Http\Controllers\Manager\TaskController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    if (auth()->user()?->isOwner()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('manager.leads.index');
})->middleware(['auth', 'billing.active'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.collect');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
});

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('stripe.webhook');

Route::get('/lead-intake', function () {
    return redirect()->route('leads.intake');
});

Route::get('/buyer-intake', [LeadIntakeController::class, 'buyer'])->name('buyers.intake');
Route::post('/buyer-intake', [LeadIntakeController::class, 'storeBuyer'])->name('buyers.intake.store');

Route::get('/seller-intake', [LeadIntakeController::class, 'seller'])->name('sellers.intake');
Route::post('/seller-intake', [LeadIntakeController::class, 'storeSeller'])->name('sellers.intake.store');

Route::get('/new-inquiry', function () {
    return view('leads.intake');
})->name('leads.intake');

Route::post('/leads', [LeadIntakeController::class, 'store'])->name('leads.intake.store');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}/signup', [EventController::class, 'signup'])->name('events.signup.show');
Route::post('/events/{slug}/signup', [EventController::class, 'storeSignup'])->name('events.signup.store');

Route::get('/mortgage-calculator', [MortgageCalculatorController::class, 'index'])->name('mortgage.calculator');
Route::post('/mortgage-calculator/send-results', [MortgageCalculatorController::class, 'sendResults'])->name('mortgage.calculator.send');

Route::middleware(['auth', 'billing.active', 'manager', 'module.enabled:lead_management'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/leads/pipeline', [LeadController::class, 'pipeline'])->name('leads.pipeline');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::delete('/leads/bulk-destroy', [LeadController::class, 'bulkDestroy'])->name('leads.bulk-destroy');
    Route::patch('/leads/bulk-restore', [LeadController::class, 'bulkRestore'])->name('leads.bulk-restore');
    Route::patch('/leads/{leadId}/restore', [LeadController::class, 'restore'])->name('leads.restore');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::patch('/leads/{lead}/status', [LeadController::class, 'moveStatus'])->name('leads.status.move');

    Route::post('/leads/{lead}/activities', [LeadActivityController::class, 'store'])->name('leads.activities.store');

    Route::post('/leads/{lead}/tasks', [TaskController::class, 'store'])->name('leads.tasks.store');
    Route::patch('/leads/{lead}/tasks/{task}', [TaskController::class, 'update'])->name('leads.tasks.update');
});

Route::middleware(['auth', 'billing.active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::middleware('module.enabled:events')->group(function () {
        Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [AdminEventController::class, 'create'])->name('events.create');
        Route::post('/events', [AdminEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [AdminEventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [AdminEventController::class, 'update'])->name('events.update');
    });

    Route::middleware('module.enabled:lead_management')->group(function () {
        Route::get('/imports/leads', [LeadImportController::class, 'index'])->name('imports.leads.index');
        Route::get('/imports/leads/template', [LeadImportController::class, 'downloadTemplate'])->name('imports.leads.template');
        Route::get('/imports/leads/export', [LeadImportController::class, 'export'])->name('imports.leads.export');
        Route::post('/imports/leads', [LeadImportController::class, 'upload'])->name('imports.leads.upload');
    });

    Route::middleware('module.enabled:email_templates')->group(function () {
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::post('/email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'test'])->name('email-templates.test');
    });

    Route::middleware('module.enabled:user_management')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/bulk-destroy', [UserManagementController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('/global-account-oversight', [GlobalAccountOversightController::class, 'index'])->name('global-account-oversight.index');
    Route::get('/plan-module-visibility', [PlanModuleVisibilityController::class, 'index'])->name('plan-module-visibility.index');
    Route::put('/plan-module-visibility', [PlanModuleVisibilityController::class, 'update'])->name('plan-module-visibility.update');

    Route::get('/prospecting-scripts', [ProspectingScriptController::class, 'index'])->name('prospecting-scripts.index');
    Route::post('/prospecting-scripts', [ProspectingScriptController::class, 'store'])->name('prospecting-scripts.store');
    Route::put('/prospecting-scripts/{prospectingScript}', [ProspectingScriptController::class, 'update'])->name('prospecting-scripts.update');
    Route::delete('/prospecting-scripts/{prospectingScript}', [ProspectingScriptController::class, 'destroy'])->name('prospecting-scripts.destroy');
});

Route::middleware(['auth', 'billing.active', 'manager', 'module.enabled:prospecting_tool'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/prospecting', [ProspectingController::class, 'index'])->name('prospecting.index');
    Route::post('/prospecting/scripts', [ProspectingController::class, 'storePrivateScript'])->name('prospecting.scripts.store');
    Route::put('/prospecting/scripts/{prospectingScript}', [ProspectingController::class, 'updatePrivateScript'])->name('prospecting.scripts.update');
    Route::post('/prospecting/parse-csv', [ProspectingController::class, 'parseCsv'])->name('prospecting.parse-csv');
    Route::post('/prospecting/session-state', [ProspectingController::class, 'updateSessionState'])->name('prospecting.session-state');
    Route::post('/prospecting/save-lead', [ProspectingController::class, 'storeLead'])->name('prospecting.save-lead');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/email-test', [ProfileController::class, 'sendEmailTest'])->name('profile.email-test');
    Route::post('/profile/subscription-portal', [ProfileController::class, 'manageSubscription'])->name('profile.subscription-portal');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

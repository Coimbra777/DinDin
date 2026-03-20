<?php

/*
|--------------------------------------------------------------------------
| CMS Routes (prefix /cms via RouteServiceProvider)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Cms\Auth\LoginController;
use App\Http\Controllers\Cms\Auth\RegisterController;
use App\Http\Controllers\Cms\BlogCategoriesController;
use App\Http\Controllers\Cms\BlogGalleryController;
use App\Http\Controllers\Cms\BlogPostsController;
use App\Http\Controllers\Cms\ClientsController;
use App\Http\Controllers\Cms\ConfigurationController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\GroupsController;
use App\Http\Controllers\Cms\PageController;
use App\Http\Controllers\Cms\UploadImageController;
use App\Http\Controllers\Cms\UsersController;
use App\Http\Controllers\Finance\Api\CreditCardApiController;
use App\Http\Controllers\Finance\Api\CategoryApiController;
use App\Http\Controllers\Finance\Api\DashboardApiController;
use App\Http\Controllers\Finance\Api\SummaryApiController;
use App\Http\Controllers\Finance\Api\TransactionApiController;
use App\Http\Controllers\Finance\Api\ProjectionApiController;
use App\Http\Controllers\Finance\Api\ReportApiController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  if (Auth::check()) {
    return redirect('/cms/dashboard');
  }

  return redirect('/cms/login');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::middleware(['auth'])->group(function () {
  if (config('finance.redirect_cms_dashboard_to_finance')) {
    Route::get('dashboard', function () {
      return redirect()->route('finance_dashboard.index');
    })->name('dashboard.index');
  } else {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
  }
  Route::resource('configurations', ConfigurationController::class);
  Route::resource('pages', PageController::class);
  Route::resource('clients', ClientsController::class);

  Route::prefix('blog')->group(function () {
    Route::resource('blog_categories', BlogCategoriesController::class);
    Route::resource('blog_posts', BlogPostsController::class);
    Route::resource('blog_posts.gallery', BlogGalleryController::class);
    Route::post('upload-images', [UploadImageController::class, 'editorUpload'])->name('upload-images');
    Route::get('/preview/{slug}', [BlogPostsController::class, 'preview'])->name('blog.preview');
  });

  Route::prefix('finance')->group(function () {
    Route::prefix('api')->group(function () {
      Route::get('dashboard', [DashboardApiController::class, 'show'])->name('finance.api.dashboard');
      Route::get('projection', [ProjectionApiController::class, 'show'])->name('finance.api.projection');
      Route::get('summary', [SummaryApiController::class, 'show'])->name('finance.api.summary');
      Route::get('transactions', [TransactionApiController::class, 'index'])->name('finance.api.transactions');
      Route::get('transactions/recent', [TransactionApiController::class, 'recent'])->name('finance.api.transactions.recent');
      Route::get('categories', [CategoryApiController::class, 'index'])->name('finance.api.categories');
      Route::post('categories', [CategoryApiController::class, 'store'])->name('finance.api.categories.store');
      Route::put('categories/{category}', [CategoryApiController::class, 'update'])->name('finance.api.categories.update');
      Route::delete('categories/{category}', [CategoryApiController::class, 'destroy'])->name('finance.api.categories.destroy');
      Route::post('transactions', [TransactionApiController::class, 'store'])->name('finance.api.transactions.store');
      Route::put('transactions/{transaction}', [TransactionApiController::class, 'update'])->name('finance.api.transactions.update');
      Route::delete('transactions/{transaction}', [TransactionApiController::class, 'destroy'])->name('finance.api.transactions.destroy');
      Route::get('credit-cards', [CreditCardApiController::class, 'index'])->name('finance.api.credit-cards.index');
      Route::post('credit-cards', [CreditCardApiController::class, 'store'])->name('finance.api.credit-cards.store');
      Route::put('credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'update'])->name('finance.api.credit-cards.update');
      Route::delete('credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'destroy'])->name('finance.api.credit-cards.destroy');
      Route::get('credit-cards/{finance_credit_card}/bill', [CreditCardApiController::class, 'bill'])->name('finance.api.credit-cards.bill');
      Route::get('reports/categories', [ReportApiController::class, 'categories'])->name('finance.api.reports.categories');
      Route::get('reports/trend', [ReportApiController::class, 'trend'])->name('finance.api.reports.trend');
      Route::prefix('goals')->group(function () {
        require base_path('routes/api/goals.php');
      });
      Route::prefix('alerts')->group(function () {
        require base_path('routes/api/alerts.php');
      });
      Route::prefix('insights')->group(function () {
        require base_path('routes/api/insights.php');
      });
      Route::prefix('credit-simulator')->group(function () {
        require base_path('routes/api/credit-simulator.php');
      });
      Route::prefix('planning')->group(function () {
        require base_path('routes/api/planning.php');
      });
    });

    Route::get('finance_dashboard', [FinanceDashboardController::class, 'index'])
      ->name('finance_dashboard.index');
    Route::resource('finance_transactions', TransactionController::class)->except(['show']);
    Route::resource('finance_categories', CategoryController::class)->except(['show']);
  });

  Route::post('logout', [LoginController::class, 'logout'])->name('logout');

  Route::prefix('admin')->group(function () {
    Route::resource('groups', GroupsController::class);
    Route::resource('users', UsersController::class);
  });
});

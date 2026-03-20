<?php

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
  if (Auth::check()) {
    return redirect('/cms/dashboard');
  }

  return redirect('/cms/login');
});

Route::get('login', 'Cms\Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Cms\Auth\LoginController@login');

Route::middleware(['auth'])->group(function () {
  if (config('finance.redirect_cms_dashboard_to_finance')) {
    Route::get('dashboard', function () {
      return redirect()->route('finance_dashboard.index');
    })->name('dashboard.index');
  } else {
    Route::get('dashboard', 'Cms\DashboardController@index')->name('dashboard.index');
  }
  Route::resource('configurations', 'Cms\ConfigurationController');
  Route::resource('pages', 'Cms\PageController');
  Route::resource('clients', 'Cms\ClientsController');

  Route::prefix('blog')->group(function () {
    Route::resource('blog_categories', 'Cms\BlogCategoriesController');
    Route::resource('blog_posts', 'Cms\BlogPostsController');
    Route::resource('blog_posts.gallery', 'Cms\BlogGalleryController');
    Route::post('upload-images', 'Cms\UploadImageController@editorUpload')->name('upload-images');
    Route::get('/preview/{slug}', 'Cms\BlogPostsController@preview')->name('blog.preview');
  });

  Route::prefix('finance')->group(function () {
    $dash = '\\'.\App\Modules\Finance\Http\Controllers\Api\DashboardApiController::class;
    $sum = '\\'.\App\Modules\Finance\Http\Controllers\Api\SummaryApiController::class;
    $tx = '\\'.\App\Modules\Finance\Http\Controllers\Api\TransactionApiController::class;
    $cat = '\\'.\App\Modules\Finance\Http\Controllers\Api\CategoryApiController::class;
    $proj = '\\'.\App\Modules\Projection\Http\Controllers\Api\ProjectionApiController::class;
    $card = '\\'.\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class;
    $rep = '\\'.\App\Modules\Reports\Http\Controllers\Api\ReportApiController::class;
    Route::prefix('api')->group(function () use ($dash, $sum, $tx, $cat, $proj, $card, $rep) {
      Route::get('dashboard', [$dash, 'show'])->name('finance.api.dashboard');
      Route::get('projection', [$proj, 'show'])->name('finance.api.projection');
      Route::get('summary', [$sum, 'show'])->name('finance.api.summary');
      Route::get('transactions', [$tx, 'index'])->name('finance.api.transactions');
      Route::get('transactions/recent', [$tx, 'recent'])->name('finance.api.transactions.recent');
      Route::get('categories', [$cat, 'index'])->name('finance.api.categories');
      Route::post('categories', [$cat, 'store'])->name('finance.api.categories.store');
      Route::put('categories/{category}', [$cat, 'update'])->name('finance.api.categories.update');
      Route::delete('categories/{category}', [$cat, 'destroy'])->name('finance.api.categories.destroy');
      Route::post('transactions', [$tx, 'store'])->name('finance.api.transactions.store');
      Route::put('transactions/{transaction}', [$tx, 'update'])->name('finance.api.transactions.update');
      Route::delete('transactions/{transaction}', [$tx, 'destroy'])->name('finance.api.transactions.destroy');
      Route::get('credit-cards', [$card, 'index'])->name('finance.api.credit-cards.index');
      Route::post('credit-cards', [$card, 'store'])->name('finance.api.credit-cards.store');
      Route::put('credit-cards/{finance_credit_card}', [$card, 'update'])->name('finance.api.credit-cards.update');
      Route::delete('credit-cards/{finance_credit_card}', [$card, 'destroy'])->name('finance.api.credit-cards.destroy');
      Route::get('credit-cards/{finance_credit_card}/bill', [$card, 'bill'])->name('finance.api.credit-cards.bill');
      Route::get('reports/categories', [$rep, 'categories'])->name('finance.api.reports.categories');
      Route::get('reports/trend', [$rep, 'trend'])->name('finance.api.reports.trend');
    });

    // Controladores fora de App\Http\Controllers: prefixo \ obrigatório (senão o namespace do grupo duplica o path)
    Route::get('finance_dashboard', ['\\'.\App\Modules\Finance\Http\Controllers\FinanceDashboardController::class, 'index'])
      ->name('finance_dashboard.index');
    Route::resource('finance_transactions', '\\'.\App\Modules\Finance\Http\Controllers\TransactionController::class)->except(['show']);
    Route::resource('finance_categories', '\\'.\App\Modules\Finance\Http\Controllers\CategoryController::class)->except(['show']);
  });

  Route::post('logout', 'Cms\Auth\LoginController@logout')->name('logout');

  Route::prefix('admin')->group(function () {
    Route::resource('groups', 'Cms\GroupsController');
    Route::resource('users', 'Cms\UsersController');
  });
});

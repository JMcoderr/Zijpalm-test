<?php
// Illuminates
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

// Livewires
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;

// Controllers
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
// Home view
Route::view('/', 'home')->name('home');

// Middleware routes
Route::middleware('auth')->group(
    function(){
        // Routes for settings
        Route::prefix('instellingen')->name('settings.')->group(
            function(){
                Route::redirect('/', 'instellingen/profiel')->name('index');
                Route::get('profiel', [UserController::class, 'edit'])->name('profile');
                Route::get('wachtwoord', Password::class)->name('password');
                Route::get('afmelden', [UserController::class, 'cancelSubscription'])->name('cancel');
                Route::delete('afmelden', [UserController::class, 'processCancelSubscription'])->name('processCancel');
            }
        );

        // Routes for user management
        Route::middleware('admin_or_self')->prefix('lid')->name('user.')->group(
            function(){
                Route::get('{user}', [UserController::class, 'edit'])->name('edit');
                Route::put('{user}', [UserController::class, 'update'])->name('update');
                Route::get('{user}/password', Password::class)->name('password');
                Route::get('{user}/afmelden', [UserController::class, 'cancelSubscription'])->name('cancel');
                Route::delete('{user}/afmelden', [UserController::class, 'processCancelSubscription'])->name('processCancel');
            }
        );
    }
);

// Activity routes
Route::prefix('activiteiten')->name('activity.')->controller(ActivityController::class)->group(
    function(){
        // Index
        Route::get('/', 'index')->name('index');

        Route::middleware('admin')->group(function () {
            // Create
            Route::get('aanmaken', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            // Notify
            Route::post('{activity}/informeer-leden', 'notifyMembers')->name('notifyMembers');
            Route::post('{activity}/informeer-deelnemers', 'notifyParticipants')->name('notifyParticipants');

            //Update
            Route::get('{activity}/bewerken', 'edit')->name('edit');
            Route::put('{activity}', 'update')->name('update');

            // Delete
            Route::delete('{activity}', 'destroy')->name('destroy');
            Route::delete('{activity}/permanent', 'permanentDelete')->name('permanentDelete');
        });

        // Read
        Route::get('{activity}', 'show')->name('show');

        // Suggestions
        Route::get('suggestie', 'suggestion')->name('suggestion');
        Route::post('suggestie', 'processSuggestion')->name('processSuggestion');
    }
);

// Application routes
Route::middleware(['auth'])->prefix('aanmeldingen')->name('application.')->controller(ApplicationController::class)->group(
    function(){
        // Create
        Route::post('{activity}', 'store')->name('store');
        Route::get('aanmaken', 'create')->name('create');

        // Update
        Route::get('{application}/bewerken', 'edit')->name('edit');
        Route::put('{application}', 'update')->name('update');

        // Delete
        Route::delete('{application}', 'destroy')->name('destroy');
    }
);

// Report routes
Route::prefix('verslagen')->name('report.')->controller(ReportController::class)->group(
    function(){
        // Index
        Route::get('/', 'index')->name('index');

        route::middleware('admin')->group(function (){
            // Create
            Route::get('aanmaken/{activity?}', 'create')->name('create');
            // Route::get('aanmaken/jaarlijks', 'create')->name('create.yearly');
            Route::post('/', 'store')->name('store');

            // Update
            Route::get('{report}/bewerken', 'edit')->name('edit');
            Route::put('{report}', 'update')->name('update');

            // Delete
            Route::delete('{report}', 'destroy')->name('destroy');
        });

        // Read
        Route::get('{report}', 'show')->name('show');
    }
);

// Information
Route::name('information.')->controller(InformationController::class)->group(
    function(){
        // Information routes
        Route::get('over-ons', 'about')->name('about');
        Route::get('lief-en-leed', 'charity')->name('charity');

        route::middleware('guest')->group(function (){
            // Membership routes
            Route::get('lid-worden', 'join')->name('join');
            Route::get('lid-worden/aanmelden', 'joinForm')->name('joinForm');
            Route::post('lid-worden/aanmelden', 'processJoinForm')->name('processJoinForm');
        });
    }
);

// Content routes
Route::middleware(['admin'])->prefix('inhoud')->name('content.')->controller(ContentController::class)->group(
    function(){
        // Index, unused at the moment, who knows if it will ever be?
        // Route::get('content', 'index')->name('index');

        // Create
        Route::get('aanmaken/{type}', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        // Update
        Route::get('bewerken/{content}', 'edit')->name('edit');
        Route::put('{content}', 'update')->name('update');

        // Delete
        Route::delete('{content}', 'destroy')->name('destroy');
    }
);
// HAS NOT BEEN IMPLEMENTED
//Route::prefix('shop')->name('product.')->controller(ProductController::class)->group(
//    function(){
//        // Index
//        Route::get('/', 'index')->name('index');
//
//        route::middleware('admin')->group(function () {
//            // Create
//            Route::get('aanmaken', 'create')->name('create');
//            Route::post('/', 'store')->name('store');
//
//            // Edit
//            Route::get('{product}/bewerken', 'edit')->name('edit');
//            Route::put('{product}', 'update')->name('update');
//
//            // Delete
//            Route::delete('{product}', 'destroy')->name('destroy');
//        });
//
//        //Read
//        Route::get('{product}', 'show')->name('show');
//    }
//);

// Payment routes
Route::prefix('mollie')->name('payment.')->controller(PaymentController::class)->group(
    function(){
        Route::get('status/{paymentId}', 'status')->name('status');
        Route::post('webhook', 'webhook')->name('webhook')->withoutMiddleware([VerifyCsrfToken::class]);
    }
);

// Admin routes
Route::middleware(['admin'])->prefix('admin')->name('admin.')->controller(AdminController::class)->group(
    function(){
        Route::redirect('/', 'admin/activiteiten')->name('index');
        Route::get('gebruikers', 'users')->name('users');
        Route::post('import-medewerkers', 'importEmployees')->name('importEmployees');
        Route::post('import-leden', 'importMembers')->name('importMembers');
        Route::delete('lidmaatschap-afmelden/{user}', 'removeUser')->name('removeUser');
        Route::post('lidmaatschap-herstellen/{user}', 'reinstateUser')->name('reinstateUser')->withTrashed();
        Route::get('verslagen', 'reports')->name('reports');
        Route::get('content', 'content')->name('content');
        Route::get('activiteiten', 'activities')->name('activities');
        Route::get('activiteiten/download/{activity}', 'downloadApplications')->name('activities.download');
        Route::get('informeer-leden', 'notifyAllMembers')->name('notifyAllMembers');
        Route::post('informeer-leden', 'notifyAllMembersPOST')->name('notifyAllMembersPOST');
        Route::get('informeer-nieuwe-medewerkers', 'notifyNewEmployees')->name('notifyNewEmployees');
        Route::post('informeer-nieuwe-medewerkers', 'notifyNewEmployeesPOST')->name('notifyNewEmployeesPOST');
    }
);

require __DIR__.'/auth.php';

// Route::get('/', function(){return view('home');})->name('home');

// TO DO: Delete this route, make sure its not referred to on ANY page!
// Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

// User routes
// Route::get('lid/{user}/bewerken', [UserController::class, 'edit'])->name('user.edit');
// Route::put('lid/{user}', [UserController::class, 'update'])->name('user.update');
// Route::delete('lid/{user}', [UserController::class, 'destroy'])->name('user.destroy');
// Route::redirect('lid', '/settings/profile')->name('user.edit');

// Route::redirect('register', '/lid-worden')->name('register');

// Routes to view and update user profiles
// Route::middleware(['auth'])->group(
//     function(){
//         Route::redirect('instellingen', 'instellingen/profiel')->name('settings');
//         Route::get('instellingen/profiel', [UserController::class, 'edit'])->name('settings.profile');
//         Route::get('instellingen/wachtwoord', Password::class)->name('settings.password');
//         Route::get('instellingen/afmelden', [UserController::class, 'cancelSubscription'])->name('settings.cancel');
//         Route::delete('instellingen/afmelden', [UserController::class, 'processCancelSubscription'])->name('settings.processCancel');

//         // Routes for admin to manage user profiles
//         Route::get('lid/{user}', [UserController::class, 'edit'])->name('user.edit');
//         Route::put('lid/{user}', [UserController::class, 'update'])->name('user.update');
//         Route::get('lid/{user}/password', Password::class)->name('user.password');
//         Route::get('lid/{user}/afmelden', [UserController::class, 'cancelSubscription'])->name('user.cancel');
//         Route::delete('lid/{user}/afmelden', [UserController::class, 'processCancelSubscription'])->name('user.processCancel');
//     }
// );

// Activity routes
// Route::get('activiteiten', [ActivityController::class, 'index'])->name('activity.index');
//     // Suggestions
//         Route::get('activiteiten/suggestie', [ActivityController::class, 'suggestion'])->name('activity.suggestion');
//         Route::post('activiteiten/suggestie', [ActivityController::class, 'processSuggestion'])->name('activity.processSuggestion');
//     // Create
//         Route::get('activiteiten/aanmaken', [ActivityController::class, 'create'])->name('activity.create');
//         Route::post('activiteiten', [ActivityController::class, 'store'])->name('activity.store');
//     // Edit & Delete
//         Route::get('activiteiten/{activity}', [ActivityController::class, 'show'])->name('activity.show');
//         Route::get('activiteiten/{activity}/bewerken', [ActivityController::class, 'edit'])->name('activity.edit');
//         Route::put('activiteiten/{activity}', [ActivityController::class, 'update'])->name('activity.update');
//         Route::delete('activiteiten/{activity}', [ActivityController::class, 'destroy'])->name('activity.destroy');

// // Application routes
//     // Create
//         Route::post('aanmeldingen/{activity}', [ApplicationController::class, 'store'])->name('application.store');
//         Route::get('aanmeldingen/aanmaken', [ApplicationController::class, 'create'])->name('application.create');
//     // Edit & Delete
//         Route::get('aanmeldingen/{application}/bewerken', [ApplicationController::class, 'edit'])->name('application.edit');
//         Route::put('aanmeldingen/{application}', [ApplicationController::class, 'update'])->name('application.update');
//         Route::delete('aanmeldingen/{application}', [ApplicationController::class, 'destroy'])->name('application.destroy');


// // Report routes
// Route::get('verslagen', [ActivityReportController::class, 'index'])->name('report.index');
//     // Create
//         Route::get('verslagen/aanmaken/{activity?}', [ActivityReportController::class, 'create'])->name('report.create');
//         Route::post('verslagen', [ActivityReportController::class, 'store'])->name('report.store');
//     // Edit & Delete
//         Route::get('verslagen/{report}', [ActivityReportController::class, 'show'])->name('report.show');
//         Route::get('verslagen/{report}/bewerken', [ActivityReportController::class, 'edit'])->name('report.edit');
//         Route::put('verslagen/{report}', [ActivityReportController::class, 'update'])->name('report.update');
//         Route::delete('verslagen/{report}', [ActivityReportController::class, 'destroy'])->name('report.destroy');

// Information routes
// Route::get('over-ons', [InformationController::class, 'about'])->name('information.about');
// Route::get('lief-en-leed', [InformationController::class, 'charity'])->name('information.charity');

// // Membership routes
// Route::get('lid-worden', [InformationController::class, 'join'])->name('information.join');
// Route::get('lid-worden/aanmelden', [InformationController::class, 'joinForm'])->name('information.joinForm');
// Route::post('lid-worden/aanmelden', [InformationController::class, 'processJoinForm'])->name('information.processJoinForm');

// Content routes
// Route::get('content', [ContentController::class, 'index'])->name('content.index');
// Route::get('content/aanmaken/{type}', [ContentController::class, 'create'])->name('content.create');
// Route::post('content', [ContentController::class, 'store'])->name('content.store');
// Route::get('content/bewerken/{content}', [ContentController::class, 'edit'])->name('content.edit');
// Route::put('content/{content}', [ContentController::class, 'update'])->name('content.update');

// Shop routes
// Route::get('shop', [ProductController::class, 'index'])->name('product.index');
//     // Create
//         Route::get('shop/aanmaken', [ProductController::class, 'create'])->name('product.create');
//         Route::post('shop', [ProductController::class, 'store'])->name('product.store');
//     // Edit & Delete
//         Route::get('shop/{product}', [ProductController::class, 'show'])->name('product.show');
//         Route::get('shop/{product}/bewerken', [ProductController::class, 'edit'])->name('product.edit');
//         Route::put('shop/{product}', [ProductController::class, 'update'])->name('product.update');
//         Route::delete('shop/{product}', [ProductController::class, 'destroy'])->name('product.destroy');

//Admin panel
// Route::redirect('/admin', '/admin/activiteiten')->name('admin');
// Route::get('admin/activiteiten', [AdminController::class, 'activities'])->name('admin.activities');
// Route::get('admin/activiteiten/download/{activity}', [AdminController::class, 'downloadApplications'])->name('admin.activities.download');
// Route::get('admin/gebruikers', [AdminController::class, 'users'])->name('admin.users');
// Route::get('admin/verslagen', [AdminController::class, 'reports'])->name('admin.reports');
// Route::get('admin/content', [AdminController::class, 'content'])->name('admin.content');

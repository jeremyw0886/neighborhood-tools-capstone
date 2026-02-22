<?php

declare(strict_types=1);

/**
 * Route definitions.
 *
 * Format: 'METHOD /path' => [ControllerClass::class, 'method']
 *
 * Placeholders use {name} syntax and are passed as arguments to the controller method.
 */

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ToolController;
use App\Controllers\BorrowController;
use App\Controllers\ProfileController;
// use App\Controllers\SearchController;
use App\Controllers\RatingController;
use App\Controllers\PaymentController;
use App\Controllers\DisputeController;
use App\Controllers\EventController;
use App\Controllers\HandoverController;
use App\Controllers\IncidentController;
use App\Controllers\NotificationController;
use App\Controllers\WaiverController;
use App\Controllers\TosController;
use App\Controllers\PageController;
use App\Controllers\CategoryController;
use App\Controllers\AdminController;

return [

    // Home
    'GET /'                        => [HomeController::class, 'index'],

    // Authentication
    'GET /login'                   => [AuthController::class, 'showLogin'],
    'POST /login'                  => [AuthController::class, 'login'],
    'GET /register'                => [AuthController::class, 'showRegister'],
    'POST /register'               => [AuthController::class, 'register'],
    'POST /logout'                 => [AuthController::class, 'logout'],
    'GET /forgot-password'         => [AuthController::class, 'showForgotPassword'],
    'POST /forgot-password'        => [AuthController::class, 'forgotPassword'],
    'GET /reset-password'          => [AuthController::class, 'showResetPassword'],
    'POST /reset-password'         => [AuthController::class, 'resetPassword'],

    // Dashboard
    'GET /dashboard'               => [DashboardController::class, 'index'],
    'GET /dashboard/lender'        => [DashboardController::class, 'lender'],
    'GET /dashboard/borrower'      => [DashboardController::class, 'borrower'],
    'GET /dashboard/history'       => [DashboardController::class, 'history'],

    // Tools
    'GET /tools'                   => [ToolController::class, 'index'],
    'GET /tools/create'            => [ToolController::class, 'create'],
    'POST /tools'                  => [ToolController::class, 'store'],
    'GET /tools/{id}'              => [ToolController::class, 'show'],
    'GET /tools/{id}/edit'         => [ToolController::class, 'edit'],
    'POST /tools/{id}'             => [ToolController::class, 'update'],
    'POST /tools/{id}/delete'      => [ToolController::class, 'delete'],
    'GET /bookmarks'               => [ToolController::class, 'bookmarks'],
    'POST /tools/{id}/bookmark'    => [ToolController::class, 'toggleBookmark'],

    // Borrowing
    'POST /borrow/request'         => [BorrowController::class, 'request'],
    'POST /borrow/{id}/approve'    => [BorrowController::class, 'approve'],
    'POST /borrow/{id}/deny'       => [BorrowController::class, 'deny'],
    'POST /borrow/{id}/cancel'     => [BorrowController::class, 'cancel'],
    'POST /borrow/{id}/return'     => [BorrowController::class, 'return'],
    'POST /borrow/{id}/extend'     => [BorrowController::class, 'extend'],

    // Profile
    'GET /profile/edit'            => [ProfileController::class, 'edit'],
    'POST /profile/edit'           => [ProfileController::class, 'update'],
    'GET /profile/{id}'            => [ProfileController::class, 'show'],

    // Search
    // 'GET /search'                  => [SearchController::class, 'index'],

    // Ratings
    'GET /rate/{borrowId}'         => [RatingController::class, 'show'],
    'POST /rate/user'              => [RatingController::class, 'rateUser'],
    'POST /rate/tool'              => [RatingController::class, 'rateTool'],

    // Payments
    'GET /payments/complete'       => [PaymentController::class, 'complete'],
    'GET /payments/deposit/{id}'   => [PaymentController::class, 'deposit'],
    'POST /payments/deposit/{id}'  => [PaymentController::class, 'processDeposit'],
    'GET /payments/history'        => [PaymentController::class, 'history'],
    'POST /api/stripe/create-intent' => [PaymentController::class, 'createStripeIntent'],
    'POST /webhook/stripe'           => [PaymentController::class, 'stripeWebhook'],

    // Disputes
    'GET /disputes'                => [DisputeController::class, 'index'],
    'GET /disputes/create/{borrowId}' => [DisputeController::class, 'create'],
    'POST /disputes'               => [DisputeController::class, 'store'],
    'GET /disputes/{id}'           => [DisputeController::class, 'show'],
    'POST /disputes/{id}/message'  => [DisputeController::class, 'addMessage'],

    // Events
    'GET /events/create'           => [EventController::class, 'create'],
    'GET /events'                  => [EventController::class, 'index'],
    'POST /events'                 => [EventController::class, 'store'],
    'POST /events/{id}/rsvp'       => [EventController::class, 'toggleRsvp'],
    'GET /events/{id}'             => [EventController::class, 'show'],

    // Handover
    'GET /handover/{borrowId}'     => [HandoverController::class, 'verify'],
    'POST /handover/{borrowId}'    => [HandoverController::class, 'confirm'],

    // Incidents
    'GET /incidents/create/{borrowId}' => [IncidentController::class, 'create'],
    'POST /incidents'              => [IncidentController::class, 'store'],
    'GET /incidents/{id}'          => [IncidentController::class, 'show'],

    // Notifications
    'GET /notifications'           => [NotificationController::class, 'index'],
    'POST /notifications/read'     => [NotificationController::class, 'markRead'],

    // Waivers
    'GET /waiver/{borrowId}'       => [WaiverController::class, 'show'],
    'POST /waiver/{borrowId}'      => [WaiverController::class, 'sign'],

    // Informational pages (progressive-enhancement fallbacks for modals)
    'GET /how-to'                  => [PageController::class, 'howTo'],
    'GET /faq'                     => [PageController::class, 'faq'],

    // Terms of Service
    'GET /tos'                     => [TosController::class, 'show'],
    'POST /tos/accept'             => [TosController::class, 'accept'],

    // Categories
    'GET /categories'              => [CategoryController::class, 'index'],

    // Admin
    'GET /admin'                   => [AdminController::class, 'dashboard'],
    'GET /admin/users'             => [AdminController::class, 'users'],
    'GET /admin/tools'             => [AdminController::class, 'tools'],
    'GET /admin/disputes'          => [DisputeController::class, 'index'],
    'GET /admin/events'            => [AdminController::class, 'events'],
    'GET /admin/incidents'         => [AdminController::class, 'incidents'],
    'GET /admin/reports'           => [AdminController::class, 'reports'],
    'GET /admin/audit-log'         => [AdminController::class, 'auditLog'],
    'GET /admin/tos'               => [AdminController::class, 'tos'],
];

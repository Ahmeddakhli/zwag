<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminPackageController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\DataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        // Registration
        Route::post('register', [RegisterController::class, 'register']);
        Route::get('registration-config', [RegisterController::class, 'getRegistrationConfig']);
        Route::post('check-user', [RegisterController::class, 'checkUser']);
        Route::post('check-password-strength', [RegisterController::class, 'checkPasswordStrength']);
        
        // Login and other auth
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-verification', [AuthController::class, 'resendVerification']);
        Route::post('social-login', [AuthController::class, 'socialLogin']);
    });

    // Public data routes
    Route::prefix('data')->group(function () {
        Route::get('religions', [DataController::class, 'religions']);
        Route::get('blood-groups', [DataController::class, 'bloodGroups']);
        Route::get('marital-statuses', [DataController::class, 'maritalStatuses']);
        Route::get('languages', [DataController::class, 'languages']);
        Route::get('packages', [DataController::class, 'packages']);
        Route::get('general-settings', [DataController::class, 'generalSettings']);
    });
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Authentication actions
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('verify-mobile', [AuthController::class, 'verifyMobile']);
        Route::post('enable-2fa', [AuthController::class, 'enableTwoFactor']);
        Route::post('disable-2fa', [AuthController::class, 'disableTwoFactor']);
        Route::post('verify-2fa', [AuthController::class, 'verifyTwoFactor']);
    });

    // Profile management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('avatar', [ProfileController::class, 'updateAvatar']);
        Route::delete('avatar', [ProfileController::class, 'deleteAvatar']);
        
        // Profile sections
        Route::put('basic-info', [ProfileController::class, 'updateBasicInfo']);
        Route::put('physical-attributes', [ProfileController::class, 'updatePhysicalAttributes']);
        Route::put('religion-info', [ProfileController::class, 'updateReligionInfo']);
        Route::put('family-info', [ProfileController::class, 'updateFamilyInfo']);
        Route::put('partner-expectations', [ProfileController::class, 'updatePartnerExpectations']);
        
        // Career information
        Route::get('career', [ProfileController::class, 'getCareerInfo']);
        Route::post('career', [ProfileController::class, 'addCareerInfo']);
        Route::put('career/{id}', [ProfileController::class, 'updateCareerInfo']);
        Route::delete('career/{id}', [ProfileController::class, 'deleteCareerInfo']);
        
        // Education information
        Route::get('education', [ProfileController::class, 'getEducationInfo']);
        Route::post('education', [ProfileController::class, 'addEducationInfo']);
        Route::put('education/{id}', [ProfileController::class, 'updateEducationInfo']);
        Route::delete('education/{id}', [ProfileController::class, 'deleteEducationInfo']);
        
        // Profile completion status
        Route::get('completion-status', [ProfileController::class, 'completionStatus']);
    });

    // Gallery management
    Route::prefix('gallery')->group(function () {
        Route::get('/', [GalleryController::class, 'index']);
        Route::post('/', [GalleryController::class, 'upload']);
        Route::delete('/{id}', [GalleryController::class, 'delete']);
        Route::put('/{id}/set-primary', [GalleryController::class, 'setPrimary']);
        Route::get('/unpublished', [GalleryController::class, 'unpublished']);
        Route::delete('/unpublished', [GalleryController::class, 'deleteUnpublished']);
    });

    // Matchmaking and search
    Route::prefix('matches')->group(function () {
        Route::get('/', [MatchController::class, 'search']);
        Route::get('/recommendations', [MatchController::class, 'recommendations']);
        Route::get('/recent-visitors', [MatchController::class, 'recentVisitors']);
        Route::get('/{id}', [MatchController::class, 'profile']);
        Route::post('/{id}/view', [MatchController::class, 'viewProfile']);
    });

    // User interactions
    Route::prefix('interactions')->group(function () {
        // Interest management
        Route::prefix('interests')->group(function () {
            Route::get('/', [MatchController::class, 'interests']);
            Route::get('/sent', [MatchController::class, 'sentInterests']);
            Route::get('/received', [MatchController::class, 'receivedInterests']);
            Route::post('/{id}/send', [MatchController::class, 'sendInterest']);
            Route::post('/{id}/accept', [MatchController::class, 'acceptInterest']);
            Route::post('/{id}/decline', [MatchController::class, 'declineInterest']);
            Route::delete('/{id}', [MatchController::class, 'removeInterest']);
        });

        // Shortlisted profiles
        Route::prefix('shortlist')->group(function () {
            Route::get('/', [MatchController::class, 'shortlistedProfiles']);
            Route::post('/{id}', [MatchController::class, 'addToShortlist']);
            Route::delete('/{id}', [MatchController::class, 'removeFromShortlist']);
        });

        // Ignored profiles
        Route::prefix('ignored')->group(function () {
            Route::get('/', [MatchController::class, 'ignoredProfiles']);
            Route::post('/{id}', [MatchController::class, 'ignoreProfile']);
            Route::delete('/{id}', [MatchController::class, 'unignoreProfile']);
        });

        // Contact views
        Route::prefix('contacts')->group(function () {
            Route::get('/', [MatchController::class, 'contactViews']);
            Route::post('/{id}/view', [MatchController::class, 'viewContact']);
            Route::get('/limit-status', [MatchController::class, 'contactLimitStatus']);
        });
    });

    // Messaging system
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'conversations']);
        Route::get('/{conversationId}', [MessageController::class, 'messages']);
        Route::post('/', [MessageController::class, 'sendMessage']);
        Route::put('/{id}', [MessageController::class, 'updateMessage']);
        Route::delete('/{id}', [MessageController::class, 'deleteMessage']);
        Route::post('/{conversationId}/mark-read', [MessageController::class, 'markAsRead']);
        Route::get('/search/users', [MessageController::class, 'searchUsers']);
        Route::get('/unread/count', [MessageController::class, 'unreadCount']);
    });

    // Package and payment system
    Route::prefix('packages')->group(function () {
        Route::get('/', [PackageController::class, 'index']);
        Route::get('/{id}', [PackageController::class, 'show']);
        Route::get('/current/status', [PackageController::class, 'currentPackage']);
        Route::post('/{id}/purchase', [PackageController::class, 'purchase']);
    });

    Route::prefix('payments')->group(function () {
        Route::get('/gateways', [PaymentController::class, 'gateways']);
        Route::post('/initiate', [PaymentController::class, 'initiatePayment']);
        Route::post('/confirm', [PaymentController::class, 'confirmPayment']);
        Route::get('/history', [PaymentController::class, 'paymentHistory']);
        Route::get('/deposits', [PaymentController::class, 'deposits']);
        Route::post('/manual-deposit', [PaymentController::class, 'manualDeposit']);
    });

    // Reports and support
    Route::prefix('reports')->group(function () {
        Route::post('/user/{id}', [ReportController::class, 'reportUser']);
        Route::get('/my-reports', [ReportController::class, 'myReports']);
        Route::get('/transactions', [ReportController::class, 'transactions']);
        Route::get('/login-history', [ReportController::class, 'loginHistory']);
    });

    Route::prefix('support')->group(function () {
        Route::get('/tickets', [SupportController::class, 'tickets']);
        Route::post('/tickets', [SupportController::class, 'createTicket']);
        Route::get('/tickets/{id}', [SupportController::class, 'showTicket']);
        Route::post('/tickets/{id}/reply', [SupportController::class, 'replyTicket']);
        Route::post('/tickets/{id}/close', [SupportController::class, 'closeTicket']);
        Route::get('/tickets/{id}/download/{attachment}', [SupportController::class, 'downloadAttachment']);
    });

    // KYC verification
    Route::prefix('kyc')->group(function () {
        Route::get('/', [ProfileController::class, 'kycStatus']);
        Route::post('/submit', [ProfileController::class, 'submitKyc']);
        Route::get('/form', [ProfileController::class, 'kycForm']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AuthController::class, 'notifications']);
        Route::post('/{id}/read', [AuthController::class, 'markNotificationRead']);
        Route::post('/read-all', [AuthController::class, 'markAllNotificationsRead']);
        Route::delete('/{id}', [AuthController::class, 'deleteNotification']);
        Route::post('/device-token', [AuthController::class, 'registerDeviceToken']);
    });

    // User dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [ProfileController::class, 'dashboardStats']);
        Route::get('/recent-activities', [ProfileController::class, 'recentActivities']);
        Route::get('/profile-views', [ProfileController::class, 'profileViews']);
    });
});

// Admin API routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // Admin dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/stats', [AdminController::class, 'stats']);

    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/active', [AdminUserController::class, 'activeUsers']);
        Route::get('/banned', [AdminUserController::class, 'bannedUsers']);
        Route::get('/email-verified', [AdminUserController::class, 'emailVerifiedUsers']);
        Route::get('/email-unverified', [AdminUserController::class, 'emailUnverifiedUsers']);
        Route::get('/mobile-verified', [AdminUserController::class, 'mobileVerifiedUsers']);
        Route::get('/mobile-unverified', [AdminUserController::class, 'mobileUnverifiedUsers']);
        Route::get('/kyc-pending', [AdminUserController::class, 'kycPendingUsers']);
        Route::get('/kyc-verified', [AdminUserController::class, 'kycVerifiedUsers']);
        Route::get('/kyc-unverified', [AdminUserController::class, 'kycUnverifiedUsers']);
        
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::post('/{id}/ban', [AdminUserController::class, 'banUser']);
        Route::post('/{id}/unban', [AdminUserController::class, 'unbanUser']);
        Route::post('/{id}/verify-email', [AdminUserController::class, 'verifyEmail']);
        Route::post('/{id}/verify-mobile', [AdminUserController::class, 'verifyMobile']);
        Route::post('/{id}/login-as', [AdminUserController::class, 'loginAsUser']);
        
        // KYC management
        Route::get('/{id}/kyc', [AdminUserController::class, 'kycDetails']);
        Route::post('/{id}/kyc/approve', [AdminUserController::class, 'approveKyc']);
        Route::post('/{id}/kyc/reject', [AdminUserController::class, 'rejectKyc']);
        
        // Send notifications
        Route::post('/{id}/notification', [AdminUserController::class, 'sendNotification']);
        Route::post('/notification/all', [AdminUserController::class, 'sendNotificationToAll']);
        Route::get('/{id}/notification-log', [AdminUserController::class, 'notificationLog']);
    });

    // Package management
    Route::prefix('packages')->group(function () {
        Route::get('/', [AdminPackageController::class, 'index']);
        Route::post('/', [AdminPackageController::class, 'store']);
        Route::get('/{id}', [AdminPackageController::class, 'show']);
        Route::put('/{id}', [AdminPackageController::class, 'update']);
        Route::delete('/{id}', [AdminPackageController::class, 'destroy']);
        Route::post('/{id}/status', [AdminPackageController::class, 'updateStatus']);
    });

    // Master data management
    Route::prefix('master-data')->group(function () {
        // Religions
        Route::prefix('religions')->group(function () {
            Route::get('/', [AdminController::class, 'religions']);
            Route::post('/', [AdminController::class, 'storeReligion']);
            Route::put('/{id}', [AdminController::class, 'updateReligion']);
            Route::delete('/{id}', [AdminController::class, 'deleteReligion']);
        });

        // Blood groups
        Route::prefix('blood-groups')->group(function () {
            Route::get('/', [AdminController::class, 'bloodGroups']);
            Route::post('/', [AdminController::class, 'storeBloodGroup']);
            Route::put('/{id}', [AdminController::class, 'updateBloodGroup']);
            Route::delete('/{id}', [AdminController::class, 'deleteBloodGroup']);
        });

        // Marital statuses
        Route::prefix('marital-statuses')->group(function () {
            Route::get('/', [AdminController::class, 'maritalStatuses']);
            Route::post('/', [AdminController::class, 'storeMaritalStatus']);
            Route::put('/{id}', [AdminController::class, 'updateMaritalStatus']);
            Route::delete('/{id}', [AdminController::class, 'deleteMaritalStatus']);
        });
    });

    // Payment gateway management
    Route::prefix('gateways')->group(function () {
        Route::get('/automatic', [AdminController::class, 'automaticGateways']);
        Route::get('/manual', [AdminController::class, 'manualGateways']);
        Route::put('/automatic/{id}', [AdminController::class, 'updateAutomaticGateway']);
        Route::put('/manual/{id}', [AdminController::class, 'updateManualGateway']);
        Route::post('/manual', [AdminController::class, 'createManualGateway']);
        Route::post('/{id}/status', [AdminController::class, 'updateGatewayStatus']);
    });

    // Transaction management
    Route::prefix('transactions')->group(function () {
        Route::get('/deposits', [AdminController::class, 'deposits']);
        Route::get('/deposits/pending', [AdminController::class, 'pendingDeposits']);
        Route::get('/deposits/approved', [AdminController::class, 'approvedDeposits']);
        Route::get('/deposits/rejected', [AdminController::class, 'rejectedDeposits']);
        Route::get('/deposits/{id}', [AdminController::class, 'depositDetails']);
        Route::post('/deposits/{id}/approve', [AdminController::class, 'approveDeposit']);
        Route::post('/deposits/{id}/reject', [AdminController::class, 'rejectDeposit']);
        Route::get('/purchases', [AdminController::class, 'purchaseHistory']);
    });

    // Reports and analytics
    Route::prefix('reports')->group(function () {
        Route::get('/user-interactions', [AdminReportController::class, 'userInteractions']);
        Route::get('/interests', [AdminReportController::class, 'interests']);
        Route::get('/ignored-profiles', [AdminReportController::class, 'ignoredProfiles']);
        Route::get('/user-reports', [AdminReportController::class, 'userReports']);
        Route::get('/login-history', [AdminReportController::class, 'loginHistory']);
        Route::get('/notification-history', [AdminReportController::class, 'notificationHistory']);
        Route::get('/revenue', [AdminReportController::class, 'revenueReports']);
        Route::get('/analytics', [AdminReportController::class, 'analytics']);
    });

    // Support ticket management
    Route::prefix('support')->group(function () {
        Route::get('/tickets', [AdminController::class, 'supportTickets']);
        Route::get('/tickets/pending', [AdminController::class, 'pendingTickets']);
        Route::get('/tickets/answered', [AdminController::class, 'answeredTickets']);
        Route::get('/tickets/closed', [AdminController::class, 'closedTickets']);
        Route::get('/tickets/{id}', [AdminController::class, 'ticketDetails']);
        Route::post('/tickets/{id}/reply', [AdminController::class, 'replyTicket']);
        Route::post('/tickets/{id}/close', [AdminController::class, 'closeTicket']);
        Route::delete('/tickets/{id}', [AdminController::class, 'deleteTicket']);
    });

    // System settings
    Route::prefix('settings')->group(function () {
        Route::get('/general', [AdminController::class, 'generalSettings']);
        Route::put('/general', [AdminController::class, 'updateGeneralSettings']);
        Route::get('/email', [AdminController::class, 'emailSettings']);
        Route::put('/email', [AdminController::class, 'updateEmailSettings']);
        Route::get('/sms', [AdminController::class, 'smsSettings']);
        Route::put('/sms', [AdminController::class, 'updateSmsSettings']);
        Route::get('/push', [AdminController::class, 'pushSettings']);
        Route::put('/push', [AdminController::class, 'updatePushSettings']);
        Route::get('/kyc', [AdminController::class, 'kycSettings']);
        Route::put('/kyc', [AdminController::class, 'updateKycSettings']);
        Route::get('/social-login', [AdminController::class, 'socialLoginSettings']);
        Route::put('/social-login', [AdminController::class, 'updateSocialLoginSettings']);
    });

    // System management
    Route::prefix('system')->group(function () {
        Route::get('/info', [AdminController::class, 'systemInfo']);
        Route::get('/optimize', [AdminController::class, 'optimize']);
        Route::get('/optimize-clear', [AdminController::class, 'optimizeClear']);
        Route::get('/cache-clear', [AdminController::class, 'clearCache']);
        Route::get('/backup', [AdminController::class, 'backup']);
        Route::get('/logs', [AdminController::class, 'logs']);
    });
});

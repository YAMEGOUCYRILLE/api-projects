

'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],

protected $routeMiddleware = [
    // ... autres middlewares
    'booking.logger' => \App\Http\Middleware\BookingLogger::class,
];
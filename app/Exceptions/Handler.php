<?php

namespace App\Exceptions;

use App\Traits\RespondsWithHttpStatus;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use RespondsWithHttpStatus;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->reportable(function (BadRequestException $e) {
            return false;
        });
    }

    public function render($request, Throwable $exception)
    {

        if ($request->expectsJson()) {
            if ($exception instanceof ModelNotFoundException) {
                logger($exception);

                return $this->failure('Model not found, the specified model could not be found.');
            }
            if ($exception instanceof MethodNotAllowedHttpException) {
                return $this->failure(
                    'Method not allowed, the specified method is not allowed.',
                    code: Response::HTTP_METHOD_NOT_ALLOWED
                );
            }
            if ($exception instanceof NotFoundHttpException) {
                return $this->failure('Invalid route, the specified route is invalid.', code: Response::HTTP_NOT_FOUND);
            }
            if ($exception instanceof ThrottleRequestsException) {
                return $this->failure(
                    'Too many request, too many requests, try again later.',
                    code: Response::HTTP_TOO_MANY_REQUESTS
                );
            }
            if ($exception instanceof AccessDeniedHttpException || $exception instanceof AuthorizationException) {
                return $this->failure('Unauthorized, this action is unauthorized.', code: Response::HTTP_UNAUTHORIZED);
            }
            if ($exception instanceof ConnectionException || $exception instanceof ConnectException) {
                return $this->failure('Bad request, an error was encountered');
            }
            if ($exception instanceof HttpException) {
                return $this->failure($exception->getMessage(), code: $exception->getCode());
            }
            if ($exception instanceof BadRequestException) {

                return $this->failure($exception->getMessage(), code: $exception->getCode());
            }
            if ($exception instanceof CustomException) {
                return $this->failure($exception->getMessage(), code: $exception->getCode());
            }
            if ($exception instanceof AmadeusException) {
                return $this->failure($exception->getMessage());
            }
        }

        return parent::render($request, $exception);
    }
}

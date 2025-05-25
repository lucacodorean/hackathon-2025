<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use App\Exception\RegisterValidationException;
use App\Exception\PasswordValidationException;
use App\Exception\UserAlreadyExistsException;
use App\Validators\RegisterValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
        private RegisterValidator $registerValidator
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response {

        $this->logger->info('Register page requested');

        /// The next two lines generate a csrf token of length 32 that will be saved in the session.
        /// It will be generated once the registration page is loaded and saved in the session.
        /// It gets unset once the registration is considered valid.
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        $this->logger->info("CSRF token: $token");

        return $this->render($response, 'auth/register.twig', [
            'csrf_token' => $token
        ]);
    }

    public function register(Request $request, Response $response): Response {
        /// To eliminate extra functionalities of the controller, I created a validator that evaluates
        /// the request. Based on the response collected in $errors, the controller will be capable of taking the best
        /// action.

        $this->logger->info('Register form route accessed.');

        try {
            $data = (array) $request->getParsedBody();
            $this->registerValidator->validate($data);

            $this->authService->register($data["username"], $data["password"], $data["csrf_token"]);

        } catch (RegisterValidationException $e) {
            $this->logger->error($e->getMessage());
            return $this->render($response, 'auth/register.twig', ['errors' => $e->getErrors()]);
        } catch (UserAlreadyExistsException $e) {
            $this->logger->error($e->getMessage());
            return $this->render($response, 'auth/register.twig', ['errors' => ['Username already exists.']]);
        }

        return $this->view->render($response, 'auth/login.twig', [
            'message' => 'Registration completed successfully! You can now log in.',
            'color'   => 'green'
        ]);
    }

    public function showLogin(Request $request, Response $response): Response {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        $this->logger->info("CSRF token: $token");

        $this->logger->info('Login page requested');
        return $this->render($response, 'auth/login.twig', [
            "csrf_token" => $token,
        ]);
    }

    public function login(Request $request, Response $response): Response {
        $this->logger->info('Login process initialized');

        $data = $request->getParsedBody();
        if ($this->authService->attempt($data["username"], $data["password"])) {
            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        }

        return $this->view->render($response, 'auth/login.twig', [
            'message' => 'Invalid credentials',
            'color'   => 'red'
        ])->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response {

        $this->logger->info('Logout action requested');
        $this->authService->logout();
        return $this->view->render($response, 'auth/login.twig', [
            'message' => 'You\'ve been logged out.',
            'color'   => 'orange'
        ])->withStatus(302);
    }
}

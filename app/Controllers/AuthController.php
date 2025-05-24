<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use App\Exception\RegisterValidationException;
use App\Exception\PasswordValidationException;
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

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration

        /// In order to eliminate extra functionalities of the controller, I created a validator that evaluates
        /// the request. Based o the response collected in $errors, the controller will be capable of taking the best
        ///  action.

        $this->logger->info('Register form route accessed.');

        try {
            $data = (array) $request->getParsedBody();
            $this->registerValidator->validate($data);

            $this->authService->register($data["username"], $data["password"]);

        } catch (RegisterValidationException $e) {
            $this->logger->error($e->getMessage());
            return $this->render($response, 'auth/register.twig', ['errors' => $e->getErrors()]);
        }

        return $this->view->render($response, 'auth/login.twig', [
            'message' => 'Registration completed successfully! You can now log in.',
            'color'   => 'green'
        ]);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        $this->logger->info('Login page requested');
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures
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
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}

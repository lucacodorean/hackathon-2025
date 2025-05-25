<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use App\Domain\Service\ExpenseService;
use App\Exception\ExpenseValidationException;
use App\Exception\NotAuthorizedException;
use App\Exception\ResourceNotFoundException;
use App\Validators\ExpenseValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly AuthService $authService,
        private readonly ExpenseValidator $validator
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        // Given that I saved the user_id post-login in user's session, it can be easily accessed.
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $page = $page < 0 ? 1 : $page;
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $selectedYear = $request->getQueryParams()['year'] ?? date("Y");
        $selectedMonth = $request->getQueryParams()['month'] ?? date("m");

        try {
            $user = $this->authService->retrieveLogged();

            $expenses = $this->expenseService->list(
                $user,
                intval($selectedYear),
                intval($selectedMonth),
                $page,
                $pageSize
            );

            $expensesCount = $this->expenseService->countBy($user, intval($selectedYear), intval($selectedMonth));
            $lastPage =  (int)($expensesCount / $pageSize) + ($expensesCount%$pageSize ? 1 : 0);
            $page = min($page, $lastPage);

            return $this->render($response, 'expenses/index.twig', [
                'currentUserId'         => $_SESSION['user_id'],
                'expenses'              => $expenses,
                'expensesCount'         => $expensesCount,
                'selectedMonth'         => $selectedMonth,
                'selectedYear'          => $selectedYear,
                "years"                 => $this->expenseService->listExpenditureYears($user),
                'page'                  => $page,
                'lastPage'              => $lastPage,
                'from'                  => ($page - 1) * $pageSize + 1,
                'to'                    => min($page * $pageSize, $expensesCount),
                'pageSize'              => $pageSize,
                'flash'                 => $flash,
            ]);

        } catch (NotAuthorizedException) {
            throw new HttpForbiddenException($request);
        }
    }

    private function getCategories() {
        $categoriesArr = $_ENV['EXPENSE_CATEGORIES'] ?? '';
        return array_filter(
            array_map('trim', explode(',', $categoriesArr)),
            fn($category) => $category !== ''
        );
    }

    public function create(Request $request, Response $response): Response
    {

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        /// Using the given hint, what I'm trying to do is to retrieve the categories from the env and then
        /// map each entry separated by "," into an item itself

        $categories = $this->getCategories();

        return $this->render($response, 'expenses/create.twig', [
            'currentUserId' => $_SESSION['user_id'],
            'categories'    => $categories
        ]);
    }

    public function store(Request $request, Response $response): Response
    {

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $data = $request->getParsedBody();
        $categories = $this->getCategories();

        try {
            $this->validator->validate($data, $categories);
            $this->expenseService->create(
                $this->authService->retrieveLogged(),
                floatval($data['amount']),
                $data['description'],
                new \DateTimeImmutable($data['date']),
                $data['category'],
            );

        } catch (ExpenseValidationException $exception) {
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $exception->getErrors(),
                'categories' => $categories,
                'defaultCategory' => $categories[0],
            ]);
        } catch (NotAuthorizedException) {
            throw new HttpForbiddenException($request);
        }

        return $response
            ->withHeader('Location', '/expenses' . $request->getUri()->getQuery())
            ->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        try {

            $categories = $this->getCategories();
            $expenseId = isset($routeParams['id']) ? (int) $routeParams['id'] : -1;
            $expense = $this->expenseService->find($expenseId);

            $this->expenseService->edit($_SESSION['user_id'], $expense->getId());
            return $this->render($response, 'expenses/edit.twig', [
                'currentUserId' => $_SESSION['user_id'],
                'expense'       => $expense,
                'categories'    => $categories
            ]);
        } catch (NotAuthorizedException) {
            throw new HttpForbiddenException($request);
        } catch (ResourceNotFoundException) {
            throw new HttpNotFoundException($request);
        }
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $data = $request->getParsedBody();
        $categories = $this->getCategories();
        $expenseId = isset($routeParams['id']) ? (int) $routeParams['id'] : -1;
        $expense = $this->expenseService->find($expenseId);

        try {
            $this->validator->validate($data, $categories);

            $this->expenseService->update(
                $expense,
                floatval($data['amount']),
                $data['description'],
                new \DateTimeImmutable($data['date']),
                $data['category'],
            );

        } catch (ResourceNotFoundException) {
            throw new HttpNotFoundException($request);
        }
        catch (ExpenseValidationException $exception) {
            return $this->render($response, 'expenses/edit.twig', [
                'errors' => $exception->getErrors(),
                'currentUserId' => $_SESSION['user_id'],
                'expense'       => $expense,
                'categories'    => $categories
            ]);
        }

        return $response
            ->withHeader('Location', '/expenses' . $request->getUri()->getQuery())
            ->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $expenseId = isset($routeParams['id']) ? (int) $routeParams['id'] : -1;
        $this->expenseService->delete($this->authService->retrieveLogged(), $expenseId);

        return $response
            ->withHeader('Location',  $request->getHeaderLine('Referer') ?: '/expenses' . $request->getUri()->getQuery())
            ->withStatus(302);
    }

    public function importCsv(Request $request, Response $response): Response {
        $user = $this->authService->retrieveLogged();

        $this->expenseService->importFromCsv($user, $request->getUploadedFiles()['csv']);

        return $response
            ->withHeader('Location', '/expenses')
            ->withStatus(302);
    }
}

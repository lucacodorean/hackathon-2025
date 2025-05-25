<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AlertGenerator;
use App\Domain\Service\AuthService;
use App\Domain\Service\CategoryBudgetService;
use App\Domain\Service\ExpenseService;
use App\Domain\Service\MonthlySummaryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly MonthlySummaryService $summaryService,
        private readonly ExpenseService $expenseService,
        private readonly AuthService $authService,
        private readonly AlertGenerator $alertGenerator,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {

        $selectedYear = $request->getQueryParams()['year'] ?? date("Y");
        $selectedMonth = $request->getQueryParams()['month'] ?? date("m");

        $user = $this->authService->retrieveLogged();
        $totalForMonth = $this->summaryService->computeTotalExpenditure(
            $user,
            intval($selectedYear),
            intval($selectedMonth)
        );

        $totalsForCategories = $this->summaryService->computePerCategoryTotals(
            $user,
            intval($selectedYear),
            intval($selectedMonth)
        );

        $avgForCategories = $this->summaryService->computePerCategoryAverages(
            $user,
            intval($selectedYear),
            intval($selectedMonth),
        );

        $alerts = $this->alertGenerator->generate(
            $user,
            intval($selectedYear),
            intval($selectedMonth),
        );

        return $this->render($response, 'dashboard.twig', [
            'currentUserId'         => $_SESSION['user_id'],
            'alerts'                => $alerts,
            'totalForMonth'         => $totalForMonth,
            'years'                 => $this->expenseService->listExpenditureYears($user),
            'selectedYear'          => $selectedYear,
            'selectedMonth'         => $selectedMonth,
            'totalsForCategories'   => $totalsForCategories,
            'averagesForCategories' => $avgForCategories,
        ]);
    }
}

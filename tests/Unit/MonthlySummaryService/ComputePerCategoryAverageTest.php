<?php

namespace Tests\Unit\MonthlySummaryService;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\MonthlySummaryService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ComputePerCategoryAverageTest extends TestCase
{
    public function testComputeAveragePerCategory(): void
    {
        $user  = new User(1, 'test', password_hash("parola123", PASSWORD_DEFAULT), new DateTimeImmutable());
        $year  = 2025;
        $month = 5;

        $criteria = [
            'user_id' => $user->getId(),
            'year'    => $year,
            'month'   => $month,
        ];

        $rawData = [
            ['category_name' => 'Groceries', 'AVG(amount_cents)' => 15.00],
            ['category_name' => 'Transport', 'AVG(amount_cents)' =>  5.00],
        ];

        /// This needs to be value < 1 to properly compute the bars in frontend.
        $expectedGrandTotal = 0.2;
        $expectedFormatted = [
            0 => ['value' => 15.00, 'percent' => 75.0],
            1 => ['value' =>  5.00, 'percent' => 25.0],
        ];

        $repoMock = $this->createMock(ExpenseRepositoryInterface::class);
        $repoMock->expects($this->once())
            ->method('averageAmountsByCategory')
            ->with($criteria)
            ->willReturn($rawData);

        $service = $this->getMockBuilder(MonthlySummaryService::class)
            ->setConstructorArgs([$repoMock])
            ->onlyMethods(['computeParameters', 'formatData'])
            ->getMock();

        $service->expects($this->once())
            ->method('computeParameters')
            ->with($user, $year, $month)
            ->willReturn($criteria);


        $service->expects($this->once())
            ->method('formatData')
            ->with($rawData, $expectedGrandTotal, 'AVG')
            ->willReturn($expectedFormatted);

        $actual = $service->computePerCategoryAverages($user, $year, $month);
        $this->assertEquals($expectedFormatted, $actual);
    }
}

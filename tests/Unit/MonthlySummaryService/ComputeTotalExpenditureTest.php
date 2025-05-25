<?php

namespace MonthlySummaryService;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\MonthlySummaryService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ComputeTotalExpenditureTest extends TestCase
{
    public function testComputeTotalExpenditure(): void {
        $user  = new User(1, 'test', password_hash("parola123", PASSWORD_DEFAULT), new DateTimeImmutable());
        $year  = 2025;
        $month = 5;

        $criteria = [
            'user_id' => $user->getId(),
            'year'    => $year,
            'month'   => $month,
        ];

        $fakeTotal = 987.65;

        $repoMock = $this->createMock(ExpenseRepositoryInterface::class);
        $repoMock->expects($this->once())
            ->method('sumAmounts')
            ->with($criteria)
            ->willReturn($fakeTotal);

        $service = $this->getMockBuilder(MonthlySummaryService::class)
            ->setConstructorArgs([$repoMock])
            ->onlyMethods(['computeParameters'])
            ->getMock();

        $service->expects($this->once())
            ->method('computeParameters')
            ->with($user, $year, $month)
            ->willReturn($criteria);

        $actual = $service->computeTotalExpenditure($user, $year, $month);
        $this->assertSame($fakeTotal, $actual);
    }

}
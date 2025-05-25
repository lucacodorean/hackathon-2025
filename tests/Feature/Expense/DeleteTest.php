<?php

namespace Tests\Feature\Expense;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\ExpenseService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    public function testDeleteExpense(): void
    {
        $repo = $this->getMockBuilder(ExpenseRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user    = new User(1, 'test', 'hash', new DateTimeImmutable());
        $date    = new DateTimeImmutable('2025-01-02');
        $expense = new Expense(1, $user->getId(), $date, "groceries", 200.0, "Klausen Hamburger");

        $repo->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturnOnConsecutiveCalls($expense, null);

        $repo->expects($this->once())
            ->method('delete');

        $service = new ExpenseService($repo);
        $service->delete($user, $expense->getId());

        $this->assertNull($service->find(1));
    }
}
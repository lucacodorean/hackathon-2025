<?php

namespace Tests\Feature\Expense;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\ExpenseService;
use App\Exception\NotAuthorizedException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @throws Exception
     * @throws NotAuthorizedException
     */
    public function testCreateExpense(): void
    {
        $repo = $this->getMockBuilder(ExpenseRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $savedExpense = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function(Expense $e) use (&$savedExpense) {
                $savedExpense = $e;
            });

        $user    = new User(1, 'test', password_hash("parola123", PASSWORD_DEFAULT), new DateTimeImmutable());
        $service = new ExpenseService($repo);
        $date    = new DateTimeImmutable("2025-01-02");

        $service->create($user, 12.3, "Shopping at Vivo Cluj-Napoca", $date, "Groceries");

        $this->assertNotNull($savedExpense);
        $this->assertSame($date,            $savedExpense->getDate());
        $this->assertSame(1,        $savedExpense->getUserId());
        $this->assertSame(12.30,    $savedExpense->getAmountCents());
    }
}
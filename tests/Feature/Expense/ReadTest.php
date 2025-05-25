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

class ReadTest extends TestCase
{
    /**
     * @throws Exception
     * @throws NotAuthorizedException
     */
    public function testReadExpense(): void
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

        $repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturnCallback(function() use (&$savedExpense) {
                return $savedExpense;
            });


        $user    = new User(1, 'test', 'hash', new DateTimeImmutable());
        $service = new ExpenseService($repo);
        $date    = new DateTimeImmutable('2025-01-02');

        $service->create($user, 12.3, 'Meat and dairy', $date, 'groceries');
        $foundExpense = $service->find(1);

        $this->assertNotNull($foundExpense);
        $this->assertSame($date,             $foundExpense->getDate());
        $this->assertSame(1,        $foundExpense->getUserId());
        $this->assertSame(12.30,    $foundExpense->getAmountCents());
    }
}
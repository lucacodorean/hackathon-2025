<?php

namespace Tests\Feature\Expense;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\ExpenseService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function testUpdateExpense(): void
    {
        $repo = $this->getMockBuilder(ExpenseRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $savedExpense = null;
        $repo->expects($this->any())
            ->method('save')
            ->willReturnCallback(function(Expense $e) use (&$savedExpense) {
                $savedExpense = $e;
                return $e;
            });

        $service = new ExpenseService($repo);

        $user    = new User(1, 'test', 'hash', new DateTimeImmutable());
        $date    = new DateTimeImmutable('2025-01-02');
        $service->create($user, 200.00, 'Meat and dairy', $date, 'groceries');

        $this->assertNotNull($savedExpense);

        $newDate = new DateTimeImmutable('2025-01-03');
        $service->update($savedExpense, 250.00, 'A white fox', $newDate,"Other");

        $this->assertNotNull($savedExpense);
        $this->assertSame('A white fox',    $savedExpense->getDescription());
        $this->assertSame(250.00,           $savedExpense->getAmountCents());
        $this->assertSame($newDate,                  $savedExpense->getDate());
        $this->assertSame('Other',          $savedExpense->getCategory());
    }

}
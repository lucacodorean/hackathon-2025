<?php

namespace Tests\Feature\Expense;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\ExpenseService;
use App\Exception\NotAuthorizedException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function testUpdateExpense(): void
    {
        // This code isn't intended to validate if the user can access the edit page because we assume that he can.
        // Thus, all that's needed to be evaluated is the possibility of actually updating the given resource.

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

    public function testUserCannotUpdateOthersExpense(): void
    {
        // In this scenario the user should not be able to access the edit page of an expense that doesn't belong to him.

        $repo = $this->getMockBuilder(ExpenseRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $owner     = new User(1, 'owner', 'hash', new DateTimeImmutable());
        $otherUser = new User(2, 'other', 'hash', new DateTimeImmutable());

        $date    = new DateTimeImmutable('2025-01-02');
        $expense = new Expense(1, $owner->getId(), $date, 'Other', 270.0, 'CTP Ticket for no buss pass');

        $repo->expects($this->once())
            ->method('find')
            ->with($expense->getId())
            ->willReturn($expense);

        $service = new ExpenseService($repo);

        $this->expectException(NotAuthorizedException::class);
        $service->edit($otherUser->getId(), $expense->getId());
    }
}
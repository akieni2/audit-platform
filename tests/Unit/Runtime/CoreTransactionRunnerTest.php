<?php

namespace Tests\Unit\Runtime;

use App\Models\Department;
use App\Services\Runtime\CoreTransactionRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CoreTransactionRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_back_database_changes_when_callback_fails(): void
    {
        $runner = app(CoreTransactionRunner::class);

        try {
            $runner->run('test.rollback', function ($transaction): void {
                Department::query()->create([
                    'name' => 'Rollback Department',
                    'code' => 'RBK',
                    'type' => 'pole',
                    'active' => true,
                ]);

                throw new RuntimeException('boom');
            });

            $this->fail('The transaction should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertDatabaseMissing('departments', [
            'code' => 'RBK',
        ]);
    }

    public function test_it_executes_after_commit_callbacks_only_after_successful_commit(): void
    {
        $runner = app(CoreTransactionRunner::class);
        $afterCommitExecuted = false;

        $runner->run('test.after_commit', function ($transaction) use (&$afterCommitExecuted): void {
            Department::query()->create([
                'name' => 'Commit Department',
                'code' => 'CMT',
                'type' => 'pole',
                'active' => true,
            ]);

            $transaction->afterCommit(function () use (&$afterCommitExecuted): void {
                $afterCommitExecuted = true;
            });
        });

        $this->assertTrue($afterCommitExecuted);
        $this->assertDatabaseHas('departments', [
            'code' => 'CMT',
        ]);
    }
}

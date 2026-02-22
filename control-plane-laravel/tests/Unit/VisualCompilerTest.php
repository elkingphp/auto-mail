<?php

namespace Tests\Unit;

use App\Services\VisualQueryCompiler;
use PHPUnit\Framework\TestCase;

class VisualCompilerTest extends TestCase
{
    private VisualQueryCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new VisualQueryCompiler();
    }

    public function test_it_compiles_simple_select(): void
    {
        $ast = [
            'table' => 'users',
            'columns' => ['name', 'email']
        ];

        $sql = $this->compiler->compile($ast, 'mysql');
        
        $this->assertStringContainsString('SELECT `name`, `email` FROM `users`', $sql);
    }

    public function test_it_adds_rls_filter(): void
    {
        $ast = [
            'table' => 'users',
            'columns' => ['*']
        ];

        $sql = $this->compiler->compile($ast, 'mysql', 'dept-123');
        
        $this->assertStringContainsString("WHERE `users`.`department_id` = 'dept-123'", $sql);
    }

    public function test_it_handles_joins_with_rls(): void
    {
        $ast = [
            'table' => 'orders',
            'columns' => ['id'],
            'joins' => [
                [
                    'table' => 'users',
                    'type' => 'INNER',
                    'on' => [
                        ['col1' => 'orders.user_id', 'col2' => 'users.id', 'operator' => '=']
                    ]
                ]
            ]
        ];

        $sql = $this->compiler->compile($ast, 'mysql', 'dept-123');
        
        $this->assertStringContainsString('INNER JOIN `users` ON `orders`.`user_id` = `users`.`id`', $sql);
        $this->assertStringContainsString("WHERE `orders`.`department_id` = 'dept-123'", $sql);
    }

    public function test_postgres_wrapping(): void
    {
        $ast = [
            'table' => 'users',
            'columns' => ['name']
        ];

        $sql = $this->compiler->compile($ast, 'pgsql');
        
        $this->assertStringContainsString('SELECT "name" FROM "users"', $sql);
    }
}

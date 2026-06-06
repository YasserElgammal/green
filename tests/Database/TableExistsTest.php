<?php

namespace Tests\Database;

use App\Tables\UserTable;
use Tests\TestCase;

class TableExistsTest extends TestCase
{
    public function testExistsReturnsFalseForEmptyTable(): void
    {
        $this->assertFalse((new UserTable())->exists());
    }

    public function testExistsReturnsTrueWhenTableHasRows(): void
    {
        $this->seed();

        $this->assertTrue((new UserTable())->exists());
    }
}

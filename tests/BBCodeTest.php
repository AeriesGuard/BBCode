<?php
namespace AeriesGuard\Tests;

use AeriesGuard\BBCode;
use AeriesGuard\Token\RootToken;
use PHPUnit\Framework\TestCase;

final class BBCodeTest extends TestCase
{
    public function testCanReturnRootToken()
    {
        $this->assertInstanceOf(
            RootToken::class,
            BBCode::getRootToken('')
        );
    }
}

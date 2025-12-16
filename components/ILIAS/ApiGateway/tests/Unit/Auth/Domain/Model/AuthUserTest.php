<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Domain\Model;

use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use PHPUnit\Framework\TestCase;

class AuthUserTest extends TestCase
{
    private AuthUser $model;
    private int $id;
    private string $login;

    #[\Override]
    protected function setUp(): void
    {
        $this->model = new AuthUser(
            $this->id = 123,
            $this->login = 'foo',
        );
    }

    public function testHasAccessorToId(): void
    {
        $this->assertSame(
            $this->id,
            $this->model->getId(),
        );
    }

    public function testHasAccessorToLogin(): void
    {
        $this->assertSame(
            $this->login,
            $this->model->getLogin(),
        );
    }
}

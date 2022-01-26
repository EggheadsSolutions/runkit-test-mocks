<?php
declare(strict_types=1);

namespace Eggheads\Mocks\Test\TestCase\Lib;

use Eggheads\Mocks\Lib\ErrorMessages;
use Eggheads\Mocks\MethodMocker;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Тестирование обратной совместимости php8 -> php7
 */
class ErrorMessagesTest extends TestCase
{
    /**
     * Тестирование получения сообщения по параметрам
     *
     * @return void
     * @throws Exception
     */
    function testGetMessage(): void
    {
        MethodMocker::mock(ErrorMessages::class, '_getPhpVersion')
            ->willReturnValue('php7');
        $this->assertEquals('Too few arguments', ErrorMessages::getMessage('arguments'));
        $this->assertEquals('must be an instance of Eggheads', ErrorMessages::getMessage('instanceOf', ['appendText' => ' Eggheads']));
        $this->assertEquals('must be of the type array', ErrorMessages::getMessage('type', ['typeName' => 'array']));
        $this->assertEquals('must be of the type array or null', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'array']));
        $this->assertEquals('must be of the type string or null', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'string']));
        $this->assertEquals('must be of the type int or null', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'int']));
        $this->assertEquals('must be of the type float', ErrorMessages::getMessage('type', ['typeName' => 'float']));
        MethodMocker::restore();

        MethodMocker::mock(ErrorMessages::class, '_getPhpVersion')
            ->willReturnValue('php8');
        $this->assertEquals('Too few arguments', ErrorMessages::getMessage('arguments'));
        $this->assertEquals('must be of type Eggheads', ErrorMessages::getMessage('type', ['typeName' => 'Eggheads']));
        $this->assertEquals('must be of type array', ErrorMessages::getMessage('type', ['typeName' => 'array']));
        $this->assertEquals('must be of type ?array', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'array']));
        $this->assertEquals('must be of type ?string', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'string']));
        $this->assertEquals('must be of type ?int', ErrorMessages::getMessage('typeCanNullable', ['typeName' => 'int']));
        $this->assertEquals('must be of type float', ErrorMessages::getMessage('type', ['typeName' => 'float']));
        MethodMocker::restore();
    }
}
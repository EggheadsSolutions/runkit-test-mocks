<?php
declare(strict_types=1);

namespace Eggheads\Mocks\Test\TestCase\Lib;

use Eggheads\Mocks\MethodMocker;
use Eggheads\Mocks\Test\Lib\TestErrorMessages;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Тестирование обратной совместимости php8 -> php7
 */
class TestErrorMessagesTest extends TestCase
{
    /**
     * Тестирование получения сообщения по параметрам
     *
     * @return void
     * @throws Exception
     * @see TestErrorMessages::getMessage()
     */
    public function testGetMessage(): void
    {
        MethodMocker::mock(TestErrorMessages::class, '_getPhpVersion')
            ->willReturnValue('php7');
        $this->assertEquals('Too few arguments', TestErrorMessages::getMessage('arguments'));
        $this->assertEquals('must be an instance of Eggheads', TestErrorMessages::getMessage('instanceOf', ['appendText' => ' Eggheads']));
        $this->assertEquals('must be of the type array', TestErrorMessages::getMessage('type', ['typeName' => 'array']));
        $this->assertEquals('must be of the type array or null', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'array']));
        $this->assertEquals('must be of the type string or null', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'string']));
        $this->assertEquals('must be of the type int or null', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'int']));
        $this->assertEquals('must be of the type float', TestErrorMessages::getMessage('type', ['typeName' => 'float']));
        MethodMocker::restore();

        MethodMocker::mock(TestErrorMessages::class, '_getPhpVersion')
            ->willReturnValue('php8');
        $this->assertEquals('Too few arguments', TestErrorMessages::getMessage('arguments'));
        $this->assertEquals('must be of type Eggheads', TestErrorMessages::getMessage('type', ['typeName' => 'Eggheads']));
        $this->assertEquals('must be of type array', TestErrorMessages::getMessage('type', ['typeName' => 'array']));
        $this->assertEquals('must be of type ?array', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'array']));
        $this->assertEquals('must be of type ?string', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'string']));
        $this->assertEquals('must be of type ?int', TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'int']));
        $this->assertEquals('must be of type float', TestErrorMessages::getMessage('type', ['typeName' => 'float']));
        MethodMocker::restore();
    }
}

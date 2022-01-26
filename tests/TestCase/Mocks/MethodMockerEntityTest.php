<?php
declare(strict_types=1);

namespace Eggheads\Mocks\Test\TestCase\Mocks;

use Eggheads\Mocks\MethodMockerEntity;
use Eggheads\Mocks\Test\Lib\TestErrorMessages;
use Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestChildFixture;
use Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestFixture;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Eggheads\Mocks\MethodMockerEntity
 */
class MethodMockerEntityTest extends TestCase
{
    /**
     * тестируемые методы
     *
     * @return array<int, string[]|bool[]>
     */
    public function mockMethodsProvider(): array
    {
        return [
            ['publicFunc', false, false, false],
            ['staticFunc', true, false, false],
            ['_privateFunc', false, true, false],
            ['_privateStaticFunc', true, true, false],
            ['_protectedFunc', false, false, true],
            ['_protectedStaticFunc', true, false, true],
        ];
    }

    /**
     * тесты моков всех сочетаний public/protected/private static/non-static
     *
     * @dataProvider mockMethodsProvider
     * @param string $methodName
     * @param bool $isStatic
     * @param bool $isPrivate
     * @param bool $isProtected
     */
    public function testSimpleMocks(string $methodName, bool $isStatic, bool $isPrivate, bool $isProtected): void
    {
        if ($isStatic) {
            $instance = null;
        } else {
            $instance = new MockTestFixture();
        }
        $originalResult = $this->_callFixtureMethod($instance, $isPrivate, $isProtected);
        $mockResult = 'mock ' . $methodName;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, $methodName, false, function () use (
            $mockResult
        ) {
            return $mockResult;
        });
        self::assertEquals($mockResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
        unset($mock);

        self::assertEquals($originalResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
    }

    /**
     * Вызов нужного метода
     *
     * @param MockTestFixture|null $instance
     * @param bool $isPrivate
     * @param bool $isProtected
     * @return string
     */
    private function _callFixtureMethod(?MockTestFixture $instance, bool $isPrivate, bool $isProtected): string
    {
        if ($isPrivate) {
            if (empty($instance)) {
                return MockTestFixture::callPrivateStatic();
            } else {
                return $instance->callPrivate();
            }
        } elseif ($isProtected) {
            if (empty($instance)) {
                return MockTestFixture::callProtectedStatic();
            } else {
                return $instance->callProtected();
            }
        } else {
            if (empty($instance)) {
                return MockTestFixture::staticFunc();
            } else {
                return $instance->publicFunc();
            }
        }
    }

    /**
     * Мок на несуществующий класс
     */
    public function testMockBadClass(): void
    {
        $this->expectExceptionMessage("class \"badClass\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', 'badClass', '_protectedFunc');
    }

    /**
     * Мок на несуществующий метод
     */
    public function testMockBadMethod(): void
    {
        $this->expectExceptionMessage("method \"badMethod\" in class \"Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestFixture\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'badMethod');
    }

    /**
     * Мок с кривым экшном
     */
    public function testMockBadAction(): void
    {
        $this->expectExceptionMessage("action must be a string, a Closure or a null");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', false, 123); // @phpstan-ignore-line
    }

    /**
     * Восстановленный мок, для тестов того, что с ним ничего нельзя сделать
     *
     * @return MethodMockerEntity
     */
    private function _getRestoredMock(): MethodMockerEntity
    {
        $mock = $this->_getMock();
        $mock->expectCall(0);
        $mock->restore();
        return $mock;
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectCall(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectCall();
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectArgs(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectArgs('asd');
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectNoArgs(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectNoArgs();
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectSomeArgs(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectSomeArgs(['asd']);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectArgsList(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectArgsList([false]);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredWillReturnValue(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnValue(true);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredWillReturnAction(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnAction(function ($args) {
            return $args;
        });
    }

    /**
     * Мок вернули, а его вызывают
     */
    public function testRestoredDoAction(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->doAction([]);
    }

    /**
     * Мок вернули, а ему задают доп. перем-ю
     */
    public function testRestoredSetAdditionalVar(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->setAdditionalVar(123);
    }

    /**
     * Мок вернули, а ему задают ексепшн
     */
    public function testRestoredSetException(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willThrowException('asd');
    }

    /**
     * Мок вернули, а ему задают возвращаемые значения
     */
    public function testRestoredReturnList(): void
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnValueList([true]);
    }


    /**
     * Мок для тестов
     *
     * @return MethodMockerEntity
     */
    private function _getMock(): MethodMockerEntity
    {
        return new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', false);
    }

    /**
     * Вызывали ли мок хотя бы раз
     */
    public function testMockCallCheck(): void
    {
        $this->expectExceptionMessage("is not called!");
        $this->expectException(AssertionFailedError::class);
        $this->_getMock();
    }

    /**
     * Пустой список ожидаемых аргументов
     */
    public function testExpectArgsEmpty(): void
    {
        $this->expectExceptionMessage("method expectArgs() requires at least one arg!");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgs();
    }

    /**
     * Список ожидаемых аргументов - null
     */
    public function testExpectArgsNull(): void
    {
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgs(null);
        // При значении null не вылетел ексепшн с проверки на пустоту
        self::assertTrue(true);
    }

    /**
     * Пустой список ожидаемых аргументов
     */
    public function testExpectSomeArgsEmpty(): void
    {
        $this->expectExceptionMessage("empty arguments list for expectSomeArgs()");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectSomeArgs([]);
    }


    /**
     * Список пуст
     */
    public function testExpectedArgsListEmpty(): void
    {
        $this->expectExceptionMessage("empty args list in expectArgsList()");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([]);
    }

    /**
     * null в списке
     */
    public function testExpectedArgsListNull(): void
    {
        $this->expectExceptionMessage("args list item 1: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([false, null]);
    }

    /**
     * true в списке
     */
    public function testExpectedArgsListTrue(): void
    {
        $this->expectExceptionMessage("args list item 2: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([false, [1], true]);
    }

    /**
     * пустой массив в списке
     */
    public function testExpectedArgsListEmptyArr(): void
    {
        $this->expectExceptionMessage("args list item 0: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([[]]);
    }

    /**
     * Для тестов наследования
     *
     * @return array<int, string[]>
     */
    public function mockInheritedProvider(): array
    {
        return [
            /*
            тип вызова,
            метод переопределён?,
            замокать класс-наследник? (или родитель),
            вызывающий метод определён в наследнике? (или в родителе),
            результат - замокан? (или вернётся исходный)
            */
            ['this', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['this', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['this', 'notRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            //['this', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['this', 'isRedefined', 'mockParent', 'callFromParent', 'resultOriginal'],
            ['this', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['this', 'isRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            ['this', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],


            ['self', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['self', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['self', 'notRedefined', 'mockChild', 'callFromParent', 'resultOriginal'],
            //['self', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['self', 'isRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['self', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['self', 'isRedefined', 'mockChild', 'callFromParent', 'resultOriginal'],
            ['self', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],

            ['static', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['static', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['static', 'notRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            //['static', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['static', 'isRedefined', 'mockParent', 'callFromParent', 'resultOriginal'],
            ['static', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['static', 'isRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            ['static', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],

            ['parent', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['parent', 'notRedefined', 'mockChild', 'callFromChild', 'resultOriginal'],
            ['parent', 'isRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            ['parent', 'isRedefined', 'mockChild', 'callFromChild', 'resultOriginal'],
        ];
    }

    /**
     * тесты моков с наследованием
     *
     * @dataProvider mockInheritedProvider
     * @param string $callType тип вызова
     * @param string $redefinedParam метод переопределён?
     * @param string $mockParam замокать класс-наследник? (или родитель)
     * @param string $callParam вызываемый метод определён в наследнике? (или в родителе)
     * @param string $resultParam результат - замокан? (или вернётся исходный)
     */
    public function testInheritedMocks(string $callType, string $redefinedParam, string $mockParam, string $callParam, string $resultParam): void
    {
        $callChild = ($callParam === 'callFromChild');
        $isRedefined = ($redefinedParam === 'isRedefined');
        $mockChild = ($mockParam === 'mockChild');

        if (!$callChild && ($callType === 'parent')) {
            self::fail('бред');
        }
        $isStatic = ($callType !== 'this');
        $methodName = MockTestChildFixture::getInheritTestFuncName($isStatic, $isRedefined);
        if ($mockChild) {
            $mockClass = MockTestChildFixture::class;
        } else {
            $mockClass = MockTestFixture::class;
        }

        $testObject = new MockTestChildFixture();
        $originalResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

        $mockResult = 'mock ' . $methodName . ' ' . $callType . ' ' . (int)$mockChild . ' ' . (int)$callChild;
        $mock = new MethodMockerEntity('mockid', $mockClass, $methodName, false, "return '$mockResult';");

        if ($resultParam === 'resultMocked') {
            $expectedResult = $mockResult;
        } else {
            $expectedResult = $originalResult;
        }
        $actualResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

        self::assertEquals($expectedResult, $actualResult);
        unset($mock);

        self::assertEquals($originalResult, $testObject->call($callChild, $isStatic, $isRedefined, $callType));
    }


    /**
     * мок не отнаследованного protected метода в классе-наследнике
     */
    public function testProtectedMockChild(): void
    {
        $originalResult = MockTestChildFixture::callChildOnlyProtected();
        $mockResult = 'mock child only protected';
        $mock = new MethodMockerEntity('mockid', MockTestChildFixture::class, '_childOnlyFunc', false, "return '$mockResult';");
        self::assertEquals($mockResult, MockTestChildFixture::callChildOnlyProtected());
        unset($mock);

        self::assertEquals($originalResult, MockTestChildFixture::callChildOnlyProtected());
    }

    /**
     * нельзя просниффать при полной подмене
     */
    public function testSniff(): void
    {
        $this->expectExceptionMessage("Sniff mode does not support full mock");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', true, function () {
            return 'sniff';
        });
    }


    /**
     * нельзя мокать отнаследованное через анонимные функции
     */
    public function testMockInheritedClosure(): void
    {
        $this->expectExceptionMessage("can't mock inherited method _redefinedFunc as Closure");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestChildFixture::class, '_redefinedFunc', false, function () {
            return 'mock';
        });
    }


    /**
     * нельзя мокать отнаследованное непереопределённое
     */
    public function testMockInheritedNotRedeclared(): void
    {
        $this->expectExceptionMessage("method staticFunc is declared in parent class");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestChildFixture::class, 'staticFunc', false, 'return 123;');
    }

    /**
     * При переопределении метода его прототип должен оставаться тем же,
     * чтобы не было конфликта с наследниками
     * Должны сохраняться: тип, передача по ссылке и количество обязательных параметров
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) переменная нужна, чтоб объект сразу же не уничтожился
     */
    public function testStrictParams(): void
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'complexParams', false, 'return;');
        // при одиночном запуске теста, если что-то не так, будет strict error
        MockTestChildFixture::staticFunc();
        self::assertTrue(true); // всё хорошо, скрипт не упал
    }

    /**
     * Провайдер для проверок определения типов
     *
     * @return array
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function paramDeclareProvider(): array
    {
        $objParam = new MockTestFixture();
        $arrParam = [];
        $floatParam = 1.1;
        $stringParam = 'asd';
        $requiredParam = 1;

        if (TestErrorMessages::isPhp8Version()) {
            $instanceOf = TestErrorMessages::getMessage('type', ['typeName' => MockTestFixture::class]);
        } else {
            $instanceOf = TestErrorMessages::getMessage('instanceOf', ['typeName' => MockTestFixture::class, 'appendText' => ' ' . MockTestFixture::class]);
        }

        return [
            0 => [
                [
                    'params' => [true, $objParam, $arrParam, $floatParam, $stringParam],
                    'errorClass' => \ArgumentCountError::class,
                    'errorMsg' => TestErrorMessages::getMessage('arguments'),
                ],
            ],
            1 => [
                [
                    'params' => [true, 1, $arrParam, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => $instanceOf
                ],
            ],
            2 => [
                [
                    'params' => [true, $objParam, 1, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => TestErrorMessages::getMessage('type', ['typeName' => 'array']),
                ],
            ],
            3 => [
                [
                    'params' => [true, $objParam, $arrParam, [], $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => TestErrorMessages::getMessage('type', ['typeName' => 'float']),
                ],
            ],
            4 => [
                [
                    'params' => [true, $objParam, $arrParam, $floatParam, [], $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'string']),
                ],
            ],
            5 => [
                [
                    // тут всё ок
                    'params' => [true, $objParam, $arrParam, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => '',
                    'errorMsg' => '',
                ],
            ],
            6 => [
                [
                    // тут всё ок
                    'params' => [true, $objParam, $arrParam, $floatParam, null, $requiredParam],
                    'errorClass' => '',
                    'errorMsg' => '',
                ],
            ],
            // отсутствует тест того, что передача параметра по ссылке сохраняется
        ];
    }

    /**
     * Ещё один тест, проверяющий объявление параметров
     * Должны сохраняться: тип, передача по ссылке и количество обязательных параметров
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) Переменная нужна, чтоб объект сразу же не уничтожился
     *
     * @dataProvider paramDeclareProvider
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function testParamDeclare(array $testData): void
    {
        ['params' => $params, 'errorClass' => $errorClass, 'errorMsg' => $errorMsg] = $testData;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'complexParams', false, "return;");
        $refParam = 1;
        $useRefParam = array_shift($params);
        try {
            MockTestFixture::complexParams($refParam, ...$params);
            $error = null;
        } catch (\Throwable $e) {
            $error = $e;
        }
        if (empty($errorClass)) {
            self::assertEquals(null, $error);
        } else {
            self::assertInstanceOf($errorClass, $error);
            self::assertStringContainsString($errorMsg, $error->getMessage());
        }
    }


    /**
     * тест того, что дефолтные значения сохраняются
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) переменная нужна, чтоб объект сразу же не уничтожился
     */
    public function testDefaultValues(): void
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'defaultValues', false, 'return get_defined_vars();');
        $expectedResult = [
            'arrayParam' => ['a' => [null]],
            'floatParam' => 2.5,
            'stringParam' => 'asd',
            'boolParam' => true,
            'nullParam' => null,
        ];
        $result = MockTestFixture::defaultValues();
        self::assertEquals($expectedResult, $result);
    }

    /**
     * variadic параметры тоже должны правильно обрабатываться
     * без ... будет ошибка
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testVariadicParam(): void
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'variadicParam', false, 'return get_defined_vars();'); // переменная нужна, чтоб объект сразу же не уничтожился
        self::assertEquals(['variadicParam' => []], MockTestFixture::variadicParam());
        self::assertEquals(['variadicParam' => [1]], MockTestFixture::variadicParam(1));
        self::assertEquals(['variadicParam' => [1, 2]], MockTestFixture::variadicParam(1, 2));
    }

    /**
     * variadic с типом
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testVariadicParamType(): void
    {
        $error = TestErrorMessages::getMessage('type', ['typeName' => 'int']);
        $this->expectExceptionMessage($error);
        $this->expectException(\TypeError::class);
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'variadicParam', false, 'return get_defined_vars();');
        MockTestFixture::variadicParam('asd'); // @phpstan-ignore-line
    }


    /**
     * Сохранение типа возвращаемого значения
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testReturnTypeError(): void
    {
        $error = TestErrorMessages::getMessage('type', ['typeName' => 'int', 'appendText' => ', null returned']);
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage($error);
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnInt', false, 'return null;');
        MockTestFixture::returnInt();
    }

    /**
     * Сохранение типа возвращаемого значения
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testReturnTypeGood(): void
    {
        $returnInt = 4;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnInt', false, "return $returnInt;");
        $mock2 = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnNullable', false, 'return null;');
        self::assertEquals($returnInt, MockTestFixture::returnInt());
        self::assertEquals(null, MockTestFixture::returnNullable());
    }

    /**
     * Сохранение типа возвращаемого значения
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testReturnTypeNullableError(): void
    {
        $error = TestErrorMessages::getMessage('typeCanNullable', ['typeName' => 'int', 'appendText' => ', array returned']);
        $this->expectExceptionMessage($error);
        $this->expectException(\TypeError::class);
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnNullable', false, 'return [];');
        MockTestFixture::returnNullable();
    }
}

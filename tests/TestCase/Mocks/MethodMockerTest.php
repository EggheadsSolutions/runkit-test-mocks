<?php
declare(strict_types=1);

namespace Eggheads\Mocks\Test\TestCase\Mocks;

use Eggheads\Mocks\MethodMocker;
use Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestFixture;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Eggheads\Mocks\MethodMocker
 * @covers \Eggheads\Mocks\MethodMockerEntity
 */
class MethodMockerTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        MethodMocker::restore(true);
    }

    /**
     * простой тест
     */
    public function testSimpleMock(): void
    {
        $mockResult = 'simple mock result';
        $originalResult = MockTestFixture::staticFunc();

        MethodMocker::mock(MockTestFixture::class, 'staticFunc')->willReturnValue($mockResult);
        $result = MockTestFixture::staticFunc();
        self::assertEquals($mockResult, $result);

        MethodMocker::restore();
        $result = MockTestFixture::staticFunc();
        self::assertEquals($originalResult, $result);
    }

    /**
     * тест WillReturnAction
     */
    public function testWillReturnAction(): void
    {
        $argsCalled = ['arg1', 'arg2'];
        $isCalled = false;
        $returnValue = 'mock action return';

        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')
            ->willReturnAction(function ($argsReceived, $additionalVar) use ($argsCalled, $returnValue, &$isCalled) {
                self::assertEquals($argsCalled, $argsReceived);
                self::assertNull($additionalVar);
                $isCalled = true;
                return $returnValue;
            });

        $result = MockTestFixture::staticMethodArgs(...$argsCalled);
        self::assertTrue($isCalled);
        self::assertEquals($returnValue, $result);
    }

    /**
     * Подмена void метода
     */
    public function testVoidMock(): void
    {
        MethodMocker::mock(MockTestFixture::class, 'voidMock')
            ->singleCall()
            ->willReturnVoid();
        MockTestFixture::voidMock();
        self::assertTrue(true); // специально
    }

    /** Снифф void метода */
    public function testVoidSniff(): void
    {
        MethodMocker::sniff(MockTestFixture::class, 'voidMock')
            ->willReturnAction(function () {
                self::assertTrue(true);
            });
        MockTestFixture::voidMock();
    }

    /**
     * тест sniff
     */
    public function testSniff(): void
    {
        $argsCalled = ['arg1', 'arg2'];
        $isCalled = false;
        $returnValue = MockTestFixture::staticMethodArgs(...$argsCalled);

        MethodMocker::sniff(
            MockTestFixture::class,
            'staticMethodArgs',
            function ($argsReceived, $recievedValue, $additionalVar) use ($argsCalled, $returnValue, &$isCalled) {
                self::assertEquals($argsCalled, $argsReceived);
                self::assertEquals($returnValue, $recievedValue);
                self::assertNull($additionalVar);
                $isCalled = true;
                return 'sniff not return';
            }
        );
        $result = MockTestFixture::staticMethodArgs(...$argsCalled);
        self::assertTrue($isCalled);
        self::assertEquals($returnValue, $result);
    }

    /**
     * Дважды замокали один метов
     */
    public function testDuplicateMock(): void
    {
        $this->expectExceptionMessage("methodNoArgs already mocked!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
    }

    /**
     * Вызвали несуществующий запмоканый метод
     */
    public function testNotExistsMockCall(): void
    {
        $this->expectExceptionMessage("notExists mock object doesn't exist!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::doAction('notExists', []);
    }

    /**
     * Делаем приватную статичную функцию доступной
     */
    public function testCallPrivate(): void
    {
        self::assertEquals('original private static', MethodMocker::callPrivate(MockTestFixture::class, '_privateStaticFunc'));
    }

    /**
     * Делаем доступным protected метод
     */
    public function testCallProtected(): void
    {
        $testObject = new MockTestFixture();
        self::assertEquals('protected args test arg', MethodMocker::callPrivate($testObject, '_protectedArgs', ['test arg']));
    }

    /**
     * Несуществующий класс
     */
    public function testCallPrivateBadClass(): void
    {
        $this->expectExceptionMessage("class \"BadClass\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::callPrivate('BadClass', 'BlaBla');
    }

    /**
     * Несуществующий метод
     */
    public function testCallPrivateBadMethod(): void
    {
        $this->expectExceptionMessage("method \"BlaBla\" in class \"Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestFixture\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::callPrivate(MockTestFixture::class, 'BlaBla');
    }

    /**
     * вызов публичного
     */
    public function testCallPrivatePublic(): void
    {
        $this->expectExceptionMessage("is not private and is not protected!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::callPrivate(MockTestFixture::class, 'staticFunc');
    }

    /**
     * ожидалось без аргументов, а они есть
     */
    public function testUnexpectedArgs(): void
    {
        $this->expectExceptionMessage("expected no args, but they appeared");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectNoArgs();
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * меньше аргументов, чем ожидалось
     */
    public function testLessArgs(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd', 'qwe', 'zxc');
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * больше аргументов, чем ожидалось
     */
    public function testMoreArgs(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd');
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * не то значение аргумента
     */
    public function testBadArgs(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('asd', 'zxc');
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * аргументы не в том порядке
     */
    public function testOrderArgs(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectArgs('qwe', 'asd');
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * неправильная часть аргументов
     */
    public function testBadArgsSubset(): void
    {
        $this->expectExceptionMessage("unexpected args subset");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectSomeArgs([1 => 'asd']);
        MockTestFixture::staticMethodArgs('asd', 'qwe');
    }

    /**
     * вызов с хорошими аргументами
     */
    public function testGoodArgs(): void
    {
        $testObject = new MockTestFixture();
        $returnValue = 'mocked no args';
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectNoArgs()->willReturnValue($returnValue);
        self::assertEquals($returnValue, $testObject->methodNoArgs());

        $args = ['good', 'args'];
        $mock = MethodMocker::sniff(MockTestFixture::class, 'staticMethodArgs');
        $mock->expectArgs(...$args);
        self::assertEquals('static good args', MockTestFixture::staticMethodArgs(...$args));

        $args = ['awesome', 'arguments'];
        $mock->expectSomeArgs([1 => 'arguments']);
        self::assertEquals('static awesome arguments', MockTestFixture::staticMethodArgs(...$args));

        $arg = 'goooood arrrrgs';
        MethodMocker::sniff(MockTestFixture::class, '_protectedArgs')->expectArgs($arg);
        self::assertEquals('protected args goooood arrrrgs', MethodMocker::callPrivate($testObject, '_protectedArgs', [$arg]));
    }

    /**
     * хороший список аргументов
     */
    public function testArgsListGood(): void
    {
        $expectedArgs = [
            false,
            ['asd', 'qwe'],
            false,
            [1],
            [2],
        ];
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectArgsList($expectedArgs)
            ->willReturnValue('');

        $testObject = new MockTestFixture();
        $testObject->methodNoArgs();
        $testObject->methodNoArgs(...$expectedArgs[1]);// @phpstan-ignore-line
        $testObject->methodNoArgs();
        $testObject->methodNoArgs(...$expectedArgs[3]);// @phpstan-ignore-line
        self::assertTrue(true, 'Проверки не свалились');
    }

    /**
     * Спасок ожидаемых аргументов закончился
     */
    public function testArgsListShort(): void
    {
        $this->expectExceptionMessage("expect args list ended");
        $this->expectException(AssertionFailedError::class);
        $expectedArgs = [
            false,
        ];

        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectArgsList($expectedArgs)
            ->willReturnValue('');

        $testObject = new MockTestFixture();
        $testObject->methodNoArgs();
        $testObject->methodNoArgs();
    }

    /**
     * Ожидаемые аргументы не совпали
     */
    public function testArgsListFail(): void
    {
        $this->expectExceptionMessage("expected no args, but they appeared");
        $this->expectException(AssertionFailedError::class);
        $expectedArgs = [
            false,
            false,
        ];
        $testObject = new MockTestFixture();
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectArgsList($expectedArgs)
            ->willReturnValue('');
        $testObject->methodNoArgs();
        $testObject->methodNoArgs(123); // @phpstan-ignore-line
    }

    /**
     * не вызван
     */
    public function testNotCalled(): void
    {
        $this->expectExceptionMessage("is not called!");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs');
        MethodMocker::restore();
    }

    /**
     * вызван меньше, чем ожидалось
     */
    public function testCalledLess(): void
    {
        $this->expectExceptionMessage("unexpected call count");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->expectCall(2)
            ->willReturnValue('');
        MockTestFixture::staticMethodArgs(1, 2);
        MethodMocker::restore();
    }

    /**
     * вызван больше, чем ожидалось
     */
    public function testCalledMore(): void
    {
        $this->expectExceptionMessage("expected 1 calls, but more appeared");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs')->singleCall()
            ->willReturnValue('');
        MockTestFixture::staticMethodArgs(1, 2);
        MockTestFixture::staticMethodArgs(1, 2);
    }

    /**
     * вызов правильное количество раз
     */
    public function testGoodCallCount(): void
    {
        $testObject = new MockTestFixture();
        MethodMocker::mock(MockTestFixture::class, 'methodNoArgs')->expectCall(2)
            ->willReturnValue('');
        $testObject->methodNoArgs();
        $testObject->methodNoArgs();

        MethodMocker::sniff(MockTestFixture::class, 'staticFunc')->anyCall();
        MockTestFixture::staticFunc();
        MockTestFixture::staticFunc();
        MockTestFixture::staticFunc();

        MethodMocker::sniff(MockTestFixture::class, '_protectedArgs')->expectCall(0);
        MethodMocker::restore();
        self::assertTrue(true); // всё хорошо, не было ексепшнов
    }

    /**
     * проверка, что рестор всегда восстанавливает полностью
     */
    public function testFullRestore(): void
    {
        $mock1 = MethodMocker::mock(MockTestFixture::class, 'staticMethodArgs');
        $mock2 = MethodMocker::mock(MockTestFixture::class, 'staticFunc')->expectCall(2)
            ->willReturnValue('');
        MockTestFixture::staticFunc();
        try {
            MethodMocker::restore();
            self::fail('должен был выкинуться ексепшн');
        } catch (\Exception $e) {
            self::assertContains(' - is not called!', $e->getMessage());
        }
        self::assertTrue($mock1->isRestored());
        self::assertTrue($mock2->isRestored());
    }

    /**
     * Тестирует добавление дополнительной переменной
     */
    public function testAdditionalVar(): void
    {
        $someVar = 5;
        $mock = MethodMocker::mock(MockTestFixture::class, 'staticFunc')
            ->setAdditionalVar($someVar)
            ->willReturnAction(function ($params, $var) use ($someVar) {
                self::assertEquals([], $params, 'Неожиданные параметры');
                self::assertEquals($someVar, $var, 'Не записалась обычная (не массив) переменная');
                return '';
            });
        MockTestFixture::staticFunc();

        self::assertEquals(1, $mock->getCallCount(), 'Функция не вызвалась');
    }

    /**
     * Проверяет, что доп переменная также работает и в сниффе
     */
    public function testAdditionalVarSniff(): void
    {
        $someVar = 5;
        $sniff = MethodMocker::sniff(MockTestFixture::class, 'staticFunc')
            ->setAdditionalVar($someVar)
            ->willReturnAction(function ($params, $originalResult, $var) use ($someVar) {
                self::assertEquals([], $params, 'Неожиданные параметры');
                self::assertEquals('original public static', $originalResult, 'Неожиданные результат оригинальной функции');
                self::assertEquals($someVar, $var, 'Не записалась переменная');
            });
        MockTestFixture::staticFunc();
        self::assertEquals(1, $sniff->getCallCount(), 'Функция не вызвалась');
    }


    /**
     * Тест мока с ексепшном
     */
    public function testExpectException(): void
    {
        $this->expectExceptionMessage("test message");
        $this->expectException(\InvalidArgumentException::class);
        MethodMocker::mock(MockTestFixture::class, 'staticFunc')
            ->willThrowException('test message', \InvalidArgumentException::class);
        MockTestFixture::staticFunc();
    }

    /**
     * Тест мока с ексепшном
     */
    public function testExpectExceptionDefault(): void
    {
        $this->expectExceptionMessage("test message default");
        $this->expectException(\Exception::class);
        MethodMocker::mock(MockTestFixture::class, 'staticFunc')->willThrowException('test message default');
        MockTestFixture::staticFunc();
    }

    /**
     * тест мока со списком значений
     */
    public function testReturnList(): void
    {
        $returnList = [
            'asd',
            'qwe',
            234,
            true,
            null,
            [[[['cvb']]]],
        ];
        MethodMocker::mock(MockTestFixture::class, 'staticFuncMixedResult')->willReturnValueList($returnList);
        $returned = [
            MockTestFixture::staticFuncMixedResult(),
            MockTestFixture::staticFuncMixedResult(),
            MockTestFixture::staticFuncMixedResult(),
            MockTestFixture::staticFuncMixedResult(),
            MockTestFixture::staticFuncMixedResult(),
            MockTestFixture::staticFuncMixedResult(),
        ];
        self::assertEquals($returnList, $returned, 'Неправильно работает willReturnValueList');
    }

    /**
     * Вызовов больше, чем значений в списке
     */
    public function testReturnListMore(): void
    {
        $this->expectExceptionMessage("return value list ended");
        $this->expectException(AssertionFailedError::class);
        MethodMocker::mock(MockTestFixture::class, 'staticFunc')->willReturnValueList(['1']);
        MockTestFixture::staticFunc();
        MockTestFixture::staticFunc();
    }

    /**
     * переопределение expectArgs и willReturn
     */
    public function testRedefine(): void
    {
        $mock = MethodMocker::mock(MockTestFixture::class, 'staticFunc');

        $returnValue = 'val1';
        $expectArgs = 'arg1';
        $mock->expectArgs($expectArgs)->willReturnValue($returnValue);
        self::assertEquals($returnValue, MockTestFixture::staticFunc($expectArgs)); // @phpstan-ignore-line

        $returnValue = 'val2';
        $expectArgs = 'arg2';
        $mock->expectArgs($expectArgs)->willReturnValue($returnValue);
        self::assertEquals($returnValue, MockTestFixture::staticFunc($expectArgs)); // @phpstan-ignore-line

        $returnList = ['list1', 'list2'];
        $mock->expectNoArgs()->willReturnValueList($returnList);
        $returned = [
            MockTestFixture::staticFunc(),
            MockTestFixture::staticFunc(),
        ];
        self::assertEquals($returnList, $returned);

        $expectArgsList = [
            false,
            [123, 234],
        ];
        $returnList = ['list2', 'list3'];
        $mock->expectArgsList($expectArgsList)->willReturnValueList($returnList);
        /** @phpstan-ignore-next-line */
        $returned = [MockTestFixture::staticFunc(), MockTestFixture::staticFunc(...$expectArgsList[1])];
        self::assertEquals($returnList, $returned);

        $message = 'msg1';
        $mock->expectNoArgs()->willThrowException($message);
        try {
            MockTestFixture::staticFunc();
            self::fail();
        } catch (\Exception $e) {
            self::assertInstanceOf(\Exception::class, $e);
            self::assertEquals($message, $e->getMessage());
        }

        $message = 'msg2';
        $class = \InvalidArgumentException::class;
        $mock->willThrowException($message, $class);
        try {
            MockTestFixture::staticFunc();
            self::fail();
        } catch (\Exception $e) {
            self::assertInstanceOf($class, $e);
            self::assertEquals($message, $e->getMessage());
        }

        $returnActionValue = 'action';
        $mock->willReturnAction(function () use ($returnActionValue) {
            return $returnActionValue;
        });
        self::assertEquals($returnActionValue, MockTestFixture::staticFunc());

        $returnValue = 'val3';
        $mock->willReturnValue($returnValue);
        self::assertEquals($returnValue, MockTestFixture::staticFunc());
    }

    /**
     * переопределение expectArgs, срабатывание проверки
     */
    public function testRedefineFail(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        $mock = MethodMocker::mock(MockTestFixture::class, 'staticFunc')
            ->willReturnValue('');

        $expectArgs = 'arg1';
        $mock->expectArgs($expectArgs);
        MockTestFixture::staticFunc($expectArgs); // @phpstan-ignore-line

        $expectArgs = 'arg2';
        $mock->expectArgs($expectArgs);
        MockTestFixture::staticFunc();
    }

    /**
     * переопределение expectArgsList, срабатывание проверки
     */
    public function testRedefineListFail(): void
    {
        $this->expectExceptionMessage("unexpected args");
        $this->expectException(AssertionFailedError::class);
        $mock = MethodMocker::mock(MockTestFixture::class, 'staticFunc')
            ->willReturnValue('');

        $expectArgs = 'arg1';
        $mock->expectArgs($expectArgs);
        MockTestFixture::staticFunc($expectArgs); // @phpstan-ignore-line

        $expectArgsList = [false, ['arg2']];
        $mock->expectArgsList($expectArgsList);
        MockTestFixture::staticFunc();
        MockTestFixture::staticFunc();
    }
}

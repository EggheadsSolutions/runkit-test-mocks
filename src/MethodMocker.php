<?php
declare(strict_types=1);

namespace Eggheads\Mocks;

use LogicException;
use Eggheads\Mocks\Traits\Library;
use Exception;
use PHPUnit\Framework\Assert;
use ReflectionMethod;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Мокает методы в классах так, чтобы в основном коде не пришлось править ровным счетом ничего!
 * Необходим модуль runkit
 */
class MethodMocker
{
    use Library;

    /**
     * Стэк моков в рамках одного теста
     *
     * @var MethodMockerEntity[]
     */
    private static array $_mockList = [];

    /**
     * Мокаем метод
     *
     * @param string $className абсолютный путь к классу
     * @param string $methodName
     * @param string|null $newAction новое событие метода
     * @return MethodMockerEntity
     * @throws AssertionFailedError|Exception
     */
    public static function mock(string $className, string $methodName, ?string $newAction = null): MethodMockerEntity
    {
        self::_newMockCheck($className, $methodName);
        $key = self::_buildKey($className, $methodName);
        self::$_mockList[$key] = new MethodMockerEntity($key, $className, $methodName, false, $newAction);
        return self::$_mockList[$key];
    }

    /**
     * Снифаем метод
     *
     * @param string $className
     * @param string $methodName
     * @param null|callable $sniffAction функция, вызываемая при вызове подслушиваемого метода: function($args,
     *                                   $originalResult) {}, $originalResult - результат выполнения подслушиваемого метода
     * @return MethodMockerEntity
     * @throws AssertionFailedError|Exception
     */
    public static function sniff(string $className, string $methodName, ?callable $sniffAction = null): MethodMockerEntity
    {
        self::_newMockCheck($className, $methodName);
        $key = self::_buildKey($className, $methodName);
        self::$_mockList[$key] = new MethodMockerEntity($key, $className, $methodName, true);
        if ($sniffAction !== null) {
            self::$_mockList[$key]->willReturnAction($sniffAction);
        }
        return self::$_mockList[$key];
    }

    /**
     * Проверка на возможность замокать метод
     *
     * @param string $className
     * @param string $methodName
     * @throws AssertionFailedError|Exception
     */
    private static function _newMockCheck(string $className, string $methodName): void
    {
        $key = self::_buildKey($className, $methodName);
        if (isset(self::$_mockList[$key])) {
            self::fail($key . ' already mocked!');
        }
    }

    /**
     * Формируем уникальный ключ
     *
     * @param string $className
     * @param string $methodName
     * @return string
     */
    private static function _buildKey(string $className, string $methodName): string
    {
        return $className . '::' . $methodName;
    }

    /**
     * Мок событие
     *
     * @param string $mockKey
     * @param array $args
     * @param mixed $origMethodResult результат выполнения оригинального метода в режиме снифа
     * @return mixed
     * @throws AssertionFailedError|Exception
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function doAction(string $mockKey, array $args, $origMethodResult = null)
    {
        if (!isset(self::$_mockList[$mockKey])) {
            self::fail($mockKey . " mock object doesn't exist!");
        }

        $mockObject = self::$_mockList[$mockKey];
        return $mockObject->doAction($args, $origMethodResult);
    }

    /**
     * Возвращаем все подмененные методы
     *
     * @param bool $hasFailed был ли тест завален
     * @throws AssertionFailedError|Exception
     */
    public static function restore(bool $hasFailed = false): void
    {
        $firstError = null;
        foreach (self::$_mockList as $mock) {
            try {
                $mock->restore($hasFailed);
            } catch (Exception $e) {
                if (empty($firstError)) {
                    $firstError = $e;
                }
            }
        }

        self::$_mockList = [];
        if (!empty($firstError)) {
            throw $firstError;
        }
    }

    /**
     * Делает protected и private методы публичными
     *
     * @param object|string $object . строка с названием класса для статических, непосредственно инстанс для обычных методов
     * @param string $methodName
     * @param array|null $args аргументы вызова
     * @return mixed
     * @throws AssertionFailedError|Exception
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function callPrivate($object, string $methodName, ?array $args = null)
    {
        if (is_string($object)) {
            $className = $object;
            $object = null;
            if (!class_exists($className)) {
                self::fail('class "' . $className . '" does not exist!');
            }
        } else {
            $className = get_class($object);
        }

        if (!method_exists($className, $methodName)) {
            self::fail('method "' . $methodName . '" in class "' . $className . '" does not exist!');
        }

        $reflectionMethod = new ReflectionMethod($className, $methodName);
        if (!$reflectionMethod->isPrivate() && !$reflectionMethod->isProtected()) {
            self::fail('method "' . $methodName . '" in class "' . $className . '" is not private and is not protected!');
        }

        $reflectionMethod->setAccessible(true);
        if ($args !== null) {
            $result = $reflectionMethod->invokeArgs($object, $args);
        } else {
            $result = $reflectionMethod->invoke($object);
        }

        $reflectionMethod->setAccessible(false);
        return $result;
    }

    /**
     * Завалить тест
     * Зависимость от PHPUnit
     * Определено в одном месте на все классы
     *
     * @param string $message
     * @throws AssertionFailedError|LogicException
     */
    public static function fail(string $message): void
    {
        if (class_exists(Assert::class) && method_exists(Assert::class, 'fail')) {
            Assert::fail($message);
        } else {
            throw new LogicException($message); // @codeCoverageIgnore
        }
    }

    /**
     * Сравнение с заваливанием теста
     * Зависимость от PHPUnit
     * Определено в одном месте на все классы
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     * @throws AssertionFailedError|LogicException
     */
    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        if (class_exists(Assert::class) && method_exists(Assert::class, 'assertEquals')) {
            Assert::assertEquals($expected, $actual, $message);
        } elseif ($expected != $actual) {
            throw new LogicException($message . ' expected: ' . print_r($expected, true) . ', actual: ' . print_r($actual, true)); // @codeCoverageIgnore
        }
    }
}

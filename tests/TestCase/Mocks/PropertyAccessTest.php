<?php
declare(strict_types=1);

namespace Mocks;

use Eggheads\Mocks\PropertyAccess;
use Eggheads\Mocks\Test\TestCase\Mocks\Fixture\MockTestFixture;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Eggheads\Mocks\PropertyAccess
 */
class PropertyAccessTest extends TestCase
{
    /** тест чтения и записи */
    public function test(): void
    {
        $testObject = new MockTestFixture();
        self::assertSame('testProtected', PropertyAccess::get($testObject, '_protectedProperty'), 'Не прочиталось protected свойство');
        self::assertSame('testPrivateStatic', PropertyAccess::getStatic(MockTestFixture::class, '_privateProperty'), 'Не прочиталось private static свойство');

        $newValue = 'newTestValue';
        PropertyAccess::set($testObject, '_protectedProperty', $newValue);
        self::assertSame($newValue, PropertyAccess::get($testObject, '_protectedProperty'), 'Не записалось protected свойство');

        $newStaticValue = 'newTestStaticValue';
        PropertyAccess::setStatic(MockTestFixture::class, '_privateProperty', $newStaticValue);
        self::assertSame($newStaticValue, PropertyAccess::getStatic(MockTestFixture::class, '_privateProperty'), 'Не записалось private static свойство');
    }

    /**
     * тест несуществующего свойства
     */
    public function testBadProperty(): void
    {
        $this->expectExceptionMessage("does not exist");
        $this->expectException(Exception::class);
        PropertyAccess::setStatic(MockTestFixture::class, '_unexistent', 'asd');
    }

    /**
     * Изменение статических свойств с возможностью восстановления
     */
    public function testStaticRestore(): void
    {
        $className = MockTestFixture::class;
        $propertyName = '_privateProperty';

        $originalValue = PropertyAccess::getStatic($className, $propertyName);

        $newStaticValue = $originalValue . 'newTestStaticValue';
        PropertyAccess::setStaticAndRestore($className, $propertyName, $newStaticValue);
        self::assertSame($newStaticValue, PropertyAccess::getStatic($className, $propertyName));

        $newStaticValue .= 'evenNewerStaticValue';
        PropertyAccess::setStaticAndRestore($className, $propertyName, $newStaticValue);
        self::assertSame($newStaticValue, PropertyAccess::getStatic($className, $propertyName));

        PropertyAccess::restoreStatic($className, $propertyName);
        self::assertSame($originalValue, PropertyAccess::getStatic($className, $propertyName));
    }

    /**
     * Восстановление свойства, которое не было изменено
     */
    public function testRestoreNotModified(): void
    {
        $this->expectExceptionMessage("MockTestFixture::_privateProperty was not modified");
        $this->expectException(AssertionFailedError::class);
        PropertyAccess::restoreStatic(MockTestFixture::class, '_privateProperty');
    }

    /**
     * Восстановление свойства 2 раза
     */
    public function testRestoreTwice(): void
    {
        $this->expectExceptionMessage("MockTestFixture::_privateProperty was not modified");
        $this->expectException(AssertionFailedError::class);
        $className = MockTestFixture::class;
        $propertyName = '_privateProperty';

        PropertyAccess::setStaticAndRestore($className, $propertyName, 'aedrjhnbaeoridhno');
        PropertyAccess::restoreStatic($className, $propertyName);
        PropertyAccess::restoreStatic($className, $propertyName);
    }

    /**
     * Восстановление всех статических свойств
     */
    public function testStaticRestoreAll(): void
    {
        $className = MockTestFixture::class;
        $originalValue = PropertyAccess::getStatic($className, '_privateProperty');
        PropertyAccess::setStatic($className, '_otherProperty', $originalValue);

        self::assertSame($originalValue, PropertyAccess::getStatic($className, '_privateProperty'));
        self::assertSame($originalValue, PropertyAccess::getStatic($className, '_otherProperty'));

        $newValue = $originalValue . 'newTestStaticValue';
        PropertyAccess::setStaticAndRestore($className, '_privateProperty', $newValue);
        PropertyAccess::setStaticAndRestore($className, '_otherProperty', $newValue);
        self::assertSame($newValue, PropertyAccess::getStatic($className, '_privateProperty'));
        self::assertSame($newValue, PropertyAccess::getStatic($className, '_otherProperty'));

        PropertyAccess::restoreStaticAll();
        self::assertSame($originalValue, PropertyAccess::getStatic($className, '_privateProperty'));
        self::assertSame($originalValue, PropertyAccess::getStatic($className, '_otherProperty'));

        // restoreStaticAll() можно вызывать несколько раз без ошибок
        PropertyAccess::restoreStaticAll();
    }

    /**
     * Восстановление свойства после восстановления всего
     */
    public function testRestoreAfterRestoreAll(): void
    {
        $this->expectExceptionMessage("MockTestFixture::_privateProperty was not modified");
        $this->expectException(AssertionFailedError::class);
        $className = MockTestFixture::class;
        $propertyName = '_privateProperty';

        PropertyAccess::setStaticAndRestore($className, $propertyName, 'aedrjhnbaeoridhno');
        PropertyAccess::restoreStaticAll();
        PropertyAccess::restoreStatic($className, $propertyName);
    }
}

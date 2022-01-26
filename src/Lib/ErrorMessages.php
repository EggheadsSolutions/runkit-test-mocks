<?php
declare(strict_types=1);

namespace Eggheads\Mocks\Lib;

use LogicException;

/**
 * Ошибки для разных версий php
 */
class ErrorMessages
{
    /**
     * @type string Идентификатор PHP7
     */
    private const PHP_7_VERSION = 'php7';

    /**
     * @type string Идентификатор PHP8
     */
    private const PHP_8_VERSION = 'php8';

    /**
     * @type string Идентификатор версии PHP8
     */
    private const PHP_8_VERSION_ID = 80000;

    /**
     * @type string Ошибка "Слишком мало аргументов"
     */
    private const TOO_FEW_ARGUMENTS_MESSAGE = 'Too few arguments';

    /**
     * @type array<string, string> Ошибки для PHP7
     */
    private const PHP_7_ERROR_MESSAGES = [
        'arguments' => self::TOO_FEW_ARGUMENTS_MESSAGE,
        'instanceOf' => 'must be an instance of',
        'type' => 'must be of the type :typeName',
        'typeCanNullable' => 'must be of the type :typeName or null',
        'defined' => 'Constant :constName is not defined!'
    ];

    /**
     * @type array<string, string> Ошибки для PHP8
     */
    private const PHP_8_ERROR_MESSAGES = [
        'arguments' => self::TOO_FEW_ARGUMENTS_MESSAGE,
        'type' => 'must be of type :typeName',
        'typeCanNullable' => 'must be of type ?:typeName',
        'defined' => 'Undefined constant ":constName"'
    ];

    /**
     * Является ли это 8 версией php
     *
     * @return bool
     */
    public static function isPhp8Version(): bool
    {
        return self::_getPhpVersion() === self::PHP_8_VERSION;
    }

    /**
     * Получить сообщение ошибки
     *
     * @param string $errorType
     * @param array|null $options
     * @return string
     */
    public static function getMessage(string $errorType, ?array $options = []): string
    {
        $errorMessage = '';
        $errorTemplate = self::_getPhpVersion() === self::PHP_7_VERSION ? self::PHP_7_ERROR_MESSAGES : self::PHP_8_ERROR_MESSAGES;
        $appendText = !empty($options['appendText']) ? $options['appendText'] : '';

        if (array_key_exists($errorType, $errorTemplate)) {
            switch ($errorType) {
                case 'arguments':
                case 'instanceOf':
                    $errorMessage = $errorTemplate[$errorType] . $appendText;
                    break;
                case 'type':
                case 'typeCanNullable':
                    if (empty($options['typeName'])) {
                        throw new LogicException('Не указан "typeName"');
                    }
                    $errorMessage = strtr($errorTemplate[$errorType], [':typeName' => $options['typeName']]) . $appendText;
                    break;
                case 'defined':
                    if (empty($options['constName'])) {
                        throw new LogicException('Не указан "constName"');
                    }
                    $errorMessage = strtr($errorTemplate[$errorType], [':constName' => $options['constName']]);
                    break;
            }
        }

        if (empty($errorMessage)) {
            throw new LogicException('Не найден раздел "' . $errorType . '" ошибок');
        }

        return $errorMessage;
    }

    /**
     * Получить версию php
     *
     * @return string
     */
    private static function _getPhpVersion(): string
    {
        if (PHP_VERSION_ID >= self::PHP_8_VERSION_ID) {
            return self::PHP_8_VERSION;
        }

        return self::PHP_7_VERSION;
    }
}

<?php
namespace BryanCrowe\EncryptedType\Database\Type;

use Cake\Core\Configure;
use Cake\Database\Driver;
use Cake\Database\Type;
use Cake\Database\TypeInterface;
use Cake\Database\Type\OptionalConvertInterface;
use Cake\Utility\Security;
use InvalidArgumentException;
use PDO;

/**
 * Encrypted BLOB converter. Used to encrypt and decrypt stored data.
 */
class EncryptedType extends Type implements OptionalConvertInterface, TypeInterface
{

    /**
     * Key used for encryption.
     *
     * @var string|null
     */
    protected $key = null;

    /**
     * Constructor
     *
     * @param string|null $name The name identifying this type.
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->key = Configure::readOrFail('Encryption.key');
    }

    /**
     * Convert serialized values to PHP data types.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }

        return (string)Security::decrypt($value, $this->key);
    }

    /**
     * Marshalls request data.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Convert PHP values into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        };

        if (is_string($value)) {
            return Security::encrypt($value, $this->key);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return Security::encrypt($value->__toString(), $this->key);
        }

        if (is_scalar($value)) {
            return Security::encrypt((string)$value, $this->key);
        }

        throw new InvalidArgumentException('Cannot convert value to an encrypted string.');
    }

    /**
     * Get the correct PDO binding type for string data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    /**
     * {@inheritDoc}
     *
     * @return boolean True as database results are returned as encrypted strings.
     */
    public function requiresToPhpCast()
    {
        return true;
    }
}
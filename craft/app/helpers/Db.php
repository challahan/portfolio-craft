<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use craft\app\base\Savable;
use craft\app\dates\DateTime;
use craft\app\enums\ColumnType;
use yii\base\Exception;
use yii\db\Schema;

/**
 * Class Db
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Db
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private static $_operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

    // Public Methods
    // =========================================================================

    /**
     * Prepares an array or object’s values to be sent to the database.
     *
     * @param mixed $values The values to be prepared
     *
     * @return array The prepared values
     */
    public static function prepareValuesForDb($values)
    {
        // Normalize to an array
        $values = ArrayHelper::toArray($values);

        foreach ($values as $key => $value) {
            $values[$key] = static::prepareValueForDb($value);
        }

        return $values;
    }

    /**
     * Prepares a value to be sent to the database.
     *
     * @param mixed $value The value to be prepared
     *
     * @return mixed The prepped value
     */
    public static function prepareValueForDb($value)
    {
        // If the object explicitly defines its savable value, use that
        if ($value instanceof Savable) {
            return $value->getSavableValue();
        }

        // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
        if ($value instanceof \DateTime || DateTimeHelper::isIso8601($value)) {
            return static::prepareDateForDb($value);
        }

        // If it's an object or array, just JSON-encode it
        if (is_object($value) || is_array($value)) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * Prepares a date to be sent to the database.
     *
     * @param mixed $date The date to be prepared
     *
     * @return string|null The prepped date, or `null` if it could not be prepared
     */
    public static function prepareDateForDb($date)
    {
        $date = DateTimeHelper::toDateTime($date);

        if ($date !== false) {
            $timezone = $date->getTimezone();
            $date->setTimezone(new \DateTimeZone('UTC'));
            // TODO: MySQL specific
            $formattedDate = $date->format('Y-m-d H:i:s');
            $date->setTimezone($timezone);

            return $formattedDate;
        }

        return null;
    }

    /**
     * Integer column sizes.
     *
     * @var array
     */
    private static $_intColumnSizes = [
        ColumnType::TinyInt => 128,
        ColumnType::SmallInt => 32768,
        ColumnType::MediumInt => 8388608,
        ColumnType::Int => 2147483648,
        ColumnType::BigInt => 9223372036854775808
    ];

    /**
     * Returns a number column type, taking the min, max, and number of decimal points into account.
     *
     * @param integer $min
     * @param integer $max
     * @param integer $decimals
     *
     * @return array
     */
    public static function getNumericalColumnType($min = null, $max = null, $decimals = null)
    {
        // Normalize the arguments
        $min = is_numeric($min) ? $min : -static::$_intColumnSizes[ColumnType::Int];
        $max = is_numeric($max) ? $max : static::$_intColumnSizes[ColumnType::Int] - 1;
        $decimals = is_numeric($decimals) && $decimals > 0 ? intval($decimals) : 0;

        // Unsigned?
        $unsigned = ($min >= 0);

        // Figure out the max length
        $maxAbsSize = intval($unsigned ? $max : max(abs($min), abs($max)));
        $length = ($maxAbsSize ? mb_strlen($maxAbsSize) : 0) + $decimals;

        // Decimal or int?
        if ($decimals > 0) {
            $type = Schema::TYPE_DECIMAL."($length,$decimals)";
        } else {
            // Figure out the smallest possible int column type that will fit our min/max
            foreach (static::$_intColumnSizes as $type => $size) {
                if ($unsigned) {
                    if ($max < $size * 2) {
                        break;
                    }
                } else {
                    if ($min >= -$size && $max < $size) {
                        break;
                    }
                }
            }

            /** @noinspection PhpUndefinedVariableInspection */
            $type .= "($length)";
        }

        if ($unsigned) {
            $type .= ' unsigned';
        }

        return $type;
    }

    /**
     * Returns the maximum number of bytes a given textual column type can hold for a given database.
     *
     * @param string $columnType The textual column type to check.
     * @param string $database   The type of database to use.
     *
     * @return integer The storage capacity of the column type in bytes.
     * @throws Exception if given an unknown column type/database combination
     */
    public static function getTextualColumnStorageCapacity($columnType, $database = 'mysql')
    {
        switch ($database) {
            case 'mysql': {
                switch ($columnType) {
                    case ColumnType::TinyText: {
                        // 255 bytes
                        return 255;
                    }

                    case ColumnType::Text: {
                        // 65k
                        return 65535;
                    }

                    case ColumnType::MediumText: {
                        // 16MB
                        return 16777215;
                    }

                    case ColumnType::LongText: {
                        // 4GB
                        return 4294967295;
                    }
                }

                break;
            }
        }

        throw new Exception('Unknown column type');
    }

    /**
     * Given a length of a piece of content, returns the underlying database column type to use for saving.
     *
     * @param $contentLength
     *
     * @return string
     */
    public static function getTextualColumnTypeByContentLength($contentLength)
    {
        if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::TinyText)) {
            return Schema::TYPE_STRING;
        }

        if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::Text)) {
            return Schema::TYPE_TEXT;
        }

        if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::MediumText)) {
            // Yii doesn't support 'mediumtext' so we use our own.
            return ColumnType::MediumText;
        }

        // Yii doesn't support 'longtext' so we use our own.
        return ColumnType::LongText;
    }

    /**
     * Escapes commas and asterisks in a string so they are not treated as special characters in
     * [[Db::parseParam()]].
     *
     * @param string $value The param value.
     *
     * @return string The escaped param value.
     */
    public static function escapeParam($value)
    {
        return str_replace([',', '*'], ['\,', '\*'], $value);
    }

    /**
     * Parses a query param value and returns a [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * If the `$value` is a string, it will automatically be converted to an array, split on any commas within the
     * string (via [[ArrayHelper::toArray()]]). If that is not desired behavior, you can escape the comma
     * with a backslash before it.
     *
     * The first value can be set to either `'and'` or `'or'` to define whether *all* of the values must match, or
     * *any*. If it’s neither `'and'` nor `'or'`, then `'or'` will be assumed.
     *
     * Values can begin with the operators `'not '`, `'!='`, `'<='`, `'>='`, `'<'`, `'>'`, or `'='`. If they don’t,
     * `'='` will be assumed.
     *
     * Values can also be set to either `':empty:'` or `':notempty:'` if you want to search for empty or non-empty
     * database values. (An “empty” value is either NULL or an empty string of text).
     *
     * @param string       $column  The database column that the param is targeting.
     * @param string|array $value   The param value(s).
     * @param array        &$params The [[\yii\db\Query::$params]] array.
     *
     * @return mixed
     */
    public static function parseParam($column, $value, &$params)
    {
        // Need to do a strict check here in case $value = true
        if ($value === 'not ') {
            return '';
        }

        $conditions = [];

        $value = ArrayHelper::toArray($value);

        if (!count($value)) {
            return '';
        }

        $firstVal = StringHelper::toLowerCase(ArrayHelper::getFirstValue($value));

        if ($firstVal == 'and' || $firstVal == 'or') {
            $join = array_shift($value);
        } else {
            $join = 'or';
        }

        foreach ($value as $val) {
            static::_normalizeEmptyValue($val);
            $operator = static::_parseParamOperator($val);

            if (StringHelper::toLowerCase($val) == ':empty:') {
                if ($operator == '=') {
                    $conditions[] = ['or', $column.' is null', $column.' = ""'];
                } else {
                    $conditions[] = [
                        'and',
                        $column.' is not null',
                        $column.' != ""'
                    ];
                }
            } else {
                // Trim any whitespace from the value
                $val = trim($val);

                // This could be a LIKE condition
                if ($operator == '=' || $operator == '!=') {
                    $val = preg_replace('/^\*|(?<!\\\)\*$/', '%', $val, -1, $count);
                    $like = (bool)$count;
                } else {
                    $like = false;
                }

                // Unescape any asterisks
                $val = str_replace('\*', '*', $val);

                if ($like) {
                    $conditions[] = [
                        ($operator == '=' ? 'like' : 'not like'),
                        $column,
                        $val,
                        false
                    ];
                } else {
                    // Find a unique param name
                    $paramKey = ':'.str_replace('.', '', $column);
                    $i = 1;

                    while (isset($params[$paramKey.$i])) {
                        $i++;
                    }

                    $param = $paramKey.$i;
                    $params[$param] = $val;

                    $conditions[] = $column.$operator.$param;
                }
            }
        }

        if (count($conditions) == 1) {
            return $conditions[0];
        }

        array_unshift($conditions, $join);

        return $conditions;
    }

    /**
     * Normalizes date params and then sends them off to parseParam().
     *
     * @param string                 $column
     * @param string|array|\DateTime $value
     * @param array                  &$params
     *
     * @return mixed
     */
    public static function parseDateParam($column, $value, &$params)
    {
        $normalizedValues = [];

        $value = ArrayHelper::toArray($value);

        if (!count($value)) {
            return '';
        }

        if ($value[0] == 'and' || $value[0] == 'or') {
            $normalizedValues[] = $value[0];
            array_shift($value);
        }

        foreach ($value as $val) {
            // Is this an empty value?
            static::_normalizeEmptyValue($val);

            if ($val == ':empty:' || $val == 'not :empty:') {
                $normalizedValues[] = $val;

                // Sneak out early
                continue;
            }

            if (is_string($val)) {
                $operator = static::_parseParamOperator($val);
            } else {
                $operator = '=';
            }

            // Assume that date params are set in the system timezone
            $val = DateTimeHelper::toDateTime($val, true);

            $normalizedValues[] = $operator.static::prepareDateForDb($val);
        }

        return static::parseParam($column, $normalizedValues, $params);
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalizes “empty” values.
     *
     * @param string &$value The param value.
     */
    private static function _normalizeEmptyValue(&$value)
    {
        if ($value === null) {
            $value = ':empty:';
        } else if (StringHelper::toLowerCase($value) == ':notempty:') {
            $value = 'not :empty:';
        }
    }

    /**
     * Extracts the operator from a DB param and returns it.
     *
     * @param string &$value Te param value.
     *
     * @return string The operator.
     */
    private static function _parseParamOperator(&$value)
    {
        foreach (static::$_operators as $testOperator) {
            // Does the value start with this operator?
            $operatorLength = strlen($testOperator);

            if (strncmp(StringHelper::toLowerCase($value), $testOperator,
                    $operatorLength) == 0
            ) {
                $value = mb_substr($value, $operatorLength);

                if ($testOperator == 'not ') {
                    return '!=';
                }

                return $testOperator;
            }
        }

        return '=';
    }
}

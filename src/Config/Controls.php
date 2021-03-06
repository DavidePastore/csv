<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/csv/
* @version 6.0.0
* @package League.csv
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Csv\Config;

use CallbackFilterIterator;
use InvalidArgumentException;
use LimitIterator;
use SplFileObject;

/**
 *  A trait to configure and check CSV file and content
 *
 * @package League.csv
 * @since  6.0.0
 *
 */
trait Controls
{
    /**
     * the field delimiter (one character only)
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * the field enclosure character (one character only)
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * the field escape character (one character only)
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * the \SplFileObject flags holder
     *
     * @var integer
     */
    protected $flags = SplFileObject::READ_CSV;

    /**
     * return a SplFileOjbect
     *
     * @return \SplFileOjbect
     */
    abstract public function getIterator();

    /**
     * set the field delimeter
     *
     * @param string $delimiter
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If $delimeter is not a single character
     */
    public function setDelimiter($delimiter = ',')
    {
        if (1 != mb_strlen($delimiter)) {
            throw new InvalidArgumentException('The delimiter must be a single character');
        }
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * return the current field delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * detect the actual number of row according to a delimiter
     *
     * @param string  $delimiter a CSV delimiter
     * @param integer $nb_rows   the number of row to consider
     *
     * @return integer
     */
    protected function fetchRowsCountByDelimiter($delimiter, $nb_rows = 1)
    {
        $iterator = $this->getIterator();
        $iterator->setCsvControl($delimiter, $this->enclosure, $this->escape);
        //"reduce" the csv length to a maximum of $nb_rows
        $iterator = new LimitIterator($iterator, 0, $nb_rows);
        //return the parse rows
        $iterator = new CallbackFilterIterator($iterator, function ($row) {
            return is_array($row) && count($row) > 1;
        });

        return count(iterator_to_array($iterator, false));
    }

    /**
     * Detect the CSV file delimiter
     *
     * @param integer  $nb_rows
     * @param string[] $delimiters additional delimiters
     *
     * @return string[]
     *
     * @throws \InvalidArgumentException If $nb_rows value is invalid
     */
    public function detectDelimiterList($nb_rows = 1, array $delimiters = [])
    {
        $nb_rows = filter_var($nb_rows, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $nb_rows) {
            throw new InvalidArgumentException('`$nb_rows` must be a valid positive integer');
        }

        $delimiters = array_filter($delimiters, function ($str) {
            return 1 == mb_strlen($str);
        });
        $delimiters = array_merge([$this->delimiter, ',', ';', "\t"], $delimiters);
        $delimiters = array_unique($delimiters);
        $res = array_fill_keys($delimiters, 0);
        array_walk($res, function (&$value, $delim) use ($nb_rows) {
            $value = $this->fetchRowsCountByDelimiter($delim, $nb_rows);
        });

        arsort($res, SORT_NUMERIC);

        return array_keys(array_filter($res));
    }

    /**
     * set the field enclosure
     *
     * @param string $enclosure
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If $enclosure is not a single character
     */
    public function setEnclosure($enclosure = '"')
    {
        if (1 != mb_strlen($enclosure)) {
            throw new InvalidArgumentException('The enclosure must be a single character');
        }
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * return the current field enclosure
     *
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * set the field escape character
     *
     * @param string $escape
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If $escape is not a single character
     */
    public function setEscape($escape = "\\")
    {
        if (1 != mb_strlen($escape)) {
            throw new InvalidArgumentException('The escape character must be a single character');
        }
        $this->escape = $escape;

        return $this;
    }

    /**
     * return the current field escape character
     *
     * @return string
     */
    public function getEscape()
    {
        return $this->escape;
    }

    /**
     * Set the Flags associated to the CSV SplFileObject
     *
     * @param integer $flags
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If the argument is not a valid integer
     */
    public function setFlags($flags)
    {
        if (false === filter_var($flags, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new InvalidArgumentException('you should use a `SplFileObject` Constant');
        }

        $this->flags = $flags|SplFileObject::READ_CSV|SplFileObject::DROP_NEW_LINE;

        return $this;
    }

    /**
     * Returns the file Flags
     *
     * @return integer
     */
    public function getFlags()
    {
        return $this->flags;
    }
}

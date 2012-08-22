<?php
/**
 * A very simple unassuming ICS parser.
 *
 * Reads a ICS file and splits the information into useable arrays.
 * Does not try and interpret the specification, just provides a raw data interface.
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 * @link        http://aidanlister.com/repos/v/iCalReader.php
 */
class ICSReader
{
    /**
     * Element array of data source
     *
     * @var     array
     * @access  private
     */
    var $_source = '';

    /**
     * Line number to be parsed next
     *
     * @var     int
     * @access  private
     */
    var $_linenum = 0;

    /**
     * Parsed data
     *
     * @var     array
     * @access  private
     */
    var $_data = array();

    /**
     * Constructor
     *
     * @param   string  Path to file to be parsed
     * @access  public
     */
    function ICSReader($source)
    {
        $source = file_get_contents($source);
        $source = preg_split('/\n[A-Z]+:/', $source, -1, PREG_SPLIT_DELIM_CAPTURE);
        die(print_r($source));
        
        $source = explode('+++',$source);
        $this->_source = array_map('trim', $source);

        $this->_data['meta'] = $this->_parseMeta();
        $this->_data['events'] = $this->_parseEvents();
    }

    /**
     * Return parsed meta information
     *
     * @access  public
     * @return  array   Parsed meta information
     */
    function getMeta()
    {
        return $this->_data['meta'];
    }

    /**
     * Return parsed event information
     *
     * @access  public
     * @return  array   Parsed event information
     */
    function getEvents()
    {
        return $this->_data['events'];
    }

    /**
     * Parse meta information
     *
     * @access  private
     */
    function _parseMeta()
    {
        // Init
        $meta = array();
        $i = 0;

        // Sanity check
        if ($this->_source[$i] !== 'BEGIN:VCALENDAR') {
            return false;
        }

        // Iterate source
        $i++;
        while (isset($this->_source[$i])) {
            $line = $this->_source[$i];
            list($key, $value) = explode(':', $line, 2);

            // Meta information
            $meta[$key] = $value;

            $i++;

            // Check next line for EOM
            if ($this->_source[$i] === 'BEGIN:VEVENT') {
                break;
            }
        }

        $this->_linenum = $i;
        return $meta;
    }

    /**
     * Parse events information
     *
     * @access  private
     */
    function _parseEvents()
    {
        // Init
        $events = array();
        $i = $this->_linenum;
        $j = 0;

        // Iterate source
        while (isset($this->_source[$i])) {
            $line = $this->_source[$i];
            list($key, $value) = explode(':', $line, 2);

            // Event information
            $events[$j++] = $this->_parseEvent();
            $i = $this->_linenum;

            // Check next line for EOC
            if ($this->_source[$i] === 'END:VCALENDAR') {
                break;
            }
        }

        return $events;
    }

    /**
     * Parse a single events information
     *
     * @access  private
     */
    function _parseEvent()
    {
        // Init
        $event = array();
        $i = $this->_linenum;

        // Sanity check
        if ($this->_source[$i] !== 'BEGIN:VEVENT') {
            return false;
        }

        $i++;
        while (isset($this->_source[$i])) {
            $line = $this->_source[$i];
            echo "line is $line <br />";
            @list($key, $value) = explode(':', $line, 2);

            // Event information
            if ($key === 'DESCRIPTION') {
                $value = str_replace(
                            array("\r\n", '\n', ',', ';'),
                            array('', "\n", ',', ';'),
                            $value);
            }
            $event[$key] = $value;
            $i++;

            // Check next line for EOE
            if ($this->_source[$i] === 'END:VEVENT') {
                $this->_linenum = $i + 1;
                return $event;
            }
        }

        // Parsing error (End tag not found)
        return false;
    }
}
?>
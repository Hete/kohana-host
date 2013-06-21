<?php

defined('SYSPATH') or die('No direct access allowed.');

/**
 * Host managing system.
 * 
 * @package Host
 * @author Hète.ca Team
 * @copyright (c) 2013, Hète.ca Inc.
 */
class Host_Core implements ArrayAccess {

    /**
     * Default identifier
     * 
     * @var string 
     */
    public static $default_identifier = "default";
    public static $testing_identifier = "phpunit";

    /**
     * Current host.
     * 
     * @var Host
     */
    protected static $current;

    /**
     * Get the current host for this execution.
     * 
     * @return Host
     */
    public static function current($path = NULL, $default = NULL, $delimiter = NULL) {

        // If $_SERVER does not have SERVER_NAME, the default config will be loaded
        $identifier = Arr::get($_SERVER, 'SERVER_NAME', Host::$default_identifier);

        // Safe lookup for phpunit
        if (@preg_grep("/phpunit/", $_SERVER)) {
            $identifier = Host::$testing_identifier;
        }

        Host::$current = Host::$current ? Host::$current : Host::get($identifier);

        if ($path === NULL) {
            return Host::$current;
        }

        return Host::$current->config($path, $default, $delimiter);
    }

    /**
     * Fetch the configuration for a specified identifier (server name).
     * 
     * @param string $identifier is a host identifier to match against
     * @param string $default is the default configuration to fetch and merge
     * upon.
     * @return Host
     */
    public static function get($identifier, $default = NULL) {

        $hosts = require_once(APPPATH . 'config/host' . EXT);

        if ($default === NULL) {
            $default = static::$default_identifier;
        }

        // Fetch and unset default config
        $config = $hosts[$default];
        unset($hosts[$default]);

        // Look for matching settings
        foreach ($hosts as $regex => $host_config) {
            if (preg_match("/^$regex$/", $identifier)) {
                // Merge host config over default config              
                $config = Arr::merge($config, $host_config);
            }
        }

        return Host::factory($config);
    }

    /**
     * Initialize Kohana with setup proper to the current host.
     * 
     * @param array $settings are settings to override setup in Kohana::init.
     */
    public static function init(array $settings = NULL) {

        Kohana::$environment = Host::current('environment');

        Cookie::$salt = Host::current('cookie_salt');

        $conf = Host::current()->as_array();

        if ($settings !== NULL) {
            $conf = Arr::merge($conf, $settings);
        }

        Kohana::init($conf);
    }

    /**
     * 
     * @param array $config
     * @return \Host
     */
    public static function factory(array $config) {
        return new Host($config);
    }

    /**
     * 
     * @param array $config
     */
    public function __construct(array $config) {
        $this->_config = $config;
    }

    /**
     * 
     * @param type $path
     * @param type $default
     * @param type $delimiter
     * @return variant
     */
    public function config($path, $default = NULL, $delimiter = NULL) {
        return Arr::path($this->_config, $path, $default, $delimiter);
    }

    /**
     * Return this configuration as an array.
     * 
     * @return array
     */
    public function as_array() {
        return (array) $this->_config;
    }

    public function offsetExists($offset) {
        return isset($this->_config[$offset]);
    }

    public function offsetGet($offset) {
        return $this->_config[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->_config[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->_config[$offset]);
    }

}

?>

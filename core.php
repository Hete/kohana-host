<?php

defined('SYSPATH') or die('No direct access allowed.');

/**
 * Host managing system.
 * 
 * @package   Host
 * @author    Hète.ca Team
 * @copyright (c) 2013, Hète.ca Inc.
 * @license   http://kohanaframework.org/license
 */
class Host_Core implements ArrayAccess {

    /**
     * Current host.
     * 
     * Host::init() must have been called, otherwise this will be NULL.
     * 
     * @var Host
     */
    public static $current;

    /**
     * Fetch the configuration for a specified identifier (server name).
     * 
     * @param string $identifier every matched setup in the configuration file
     * which key match this identifier will be merged over.
     * @return Host
     */
    public static function factory($identifier, array $settings = array()) {

        $hosts = require_once(APPPATH . 'config/host' . EXT);

        // Look for matching settings
        foreach ($hosts as $regex => $host_settings) {
            if (preg_match("/^$regex$/", $identifier)) {
                // Merge host config over default config              
                $settings = Arr::merge($settings, $host_settings);
            }
        }

        return new Host($settings);
    }

    /**
     * Initialize Kohana with setup proper to the current host.
     * 
     * Also set environment and cookie salt.
     * 
     * @see Kohana::init for $base options.
     * 
     * @param array $settings are base settings for every hosts.
     */
    public static function init(array $settings = array(), $identifier = NULL) {

        if ($identifier === NULL) {

            // Auto-detection
            if ($server_name = Arr::get($_SERVER, 'SERVER_NAME')) {
                $identifier = $server_name;
            }

            // Safe lookup for phpunit
            if (@preg_grep('/phpunit/', $_SERVER)) {
                $identifier = 'phpunit';
            }
        }

        // Still NULL?        
        if ($identifier === NULL) {
            throw new Kohana_Exception('No identifier was detected. Check your configuration file.');
        }

        // Fetch the host based on the found identifier
        Host::$current = Host::factory($identifier, $settings);

        $settings = Host::$current->settings();

        /**
         * Initialize Kohana!
         */
        Kohana::init($settings);

        Kohana::$environment = Host::$current['environment'];

        Cookie::$salt = Host::$current['cookie_salt'];
    }

    protected $_settings;

    protected function __construct(array $settings) {
        $this->_settings = $settings;
    }

    public function settings() {
        return $this->_settings;
    }

    public function offsetExists($offset) {
        return isset($this->_settings[$offset]);
    }

    public function offsetGet($offset) {
        return $this->_settings[$offset];
    }

    public function offsetSet($offset, $value) {
        
    }

    public function offsetUnset($offset) {
        
    }

}

?>

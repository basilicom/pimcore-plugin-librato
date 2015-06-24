<?php
/**
 * Librato Pimcore Plugin
 */

namespace Librato;

use Pimcore\API\Plugin as PluginLib;


/**
 * Class Plugin
 *
 * @package Librato
 */
class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface {

    const SAMPLE_CONFIG_XML = "/Librato/librato.xml";
    const CONFIG_XML = '/var/config/librato.xml';

    /**
     * @var null|\Librato\Client;
     */
    private static $libratoClient = null;

    /**
     * Initialize Plugin
     *
     * Sets up maintenance event listener
     */
    public function init()
    {
        parent::init();

        \Pimcore::getEventManager()->attach("system.maintenance", array($this, 'maintenance'));
    }

    /**
     * Gathers metrics and sends them off to librato
     */
    public function maintenance()
    {

        if (!file_exists(self::getConfigName())) {
            return;
        }

        $config = new \Zend_Config_Xml(self::getConfigName());

        if (!($config->librato->get('installed', '0') == '1')) {
            return;
        }

        if (!($config->librato->get('enabled', '0') == '1')) {
            return;
        }

        $db = \Pimcore\Resource\Mysql::get();

        $libratoClient = self::getClient();

        foreach ($config->librato->metrics->metric as $metric) {
            
            $value = 0;
            $name = $metric->name;

            $sql = $metric->sql;
            if ($sql != '') {
                $value = $db->fetchOne($sql);
            }
            
            $php = $metric->php;
            if ($php != '') {
                $value = call_user_func($php);
            }
            
            switch ($metric->type) {
                
                case 'addCounter':
                    $libratoClient->addCounter($name, $value);
                    break;
                    
                case 'addGauge':
                default:
                    $libratoClient->addGauge($name, $value);
                    break;
            }
        }

        $libratoClient->flush();
    }

    /**
     * Install plugin
     *
     * Copies sample XML to website config path if it does not exist yet.
     * Sets config file parameter "installed" to 1 and "enabled" to "1"
     *
     * @return string install success|failure message
     */
    public static function install()
    {
        if (!file_exists(self::getConfigName())) {

            $defaultConfig = new \Zend_Config_Xml(PIMCORE_PLUGINS_PATH . self::SAMPLE_CONFIG_XML);
            $configWriter = new \Zend_Config_Writer_Xml();
            $configWriter->setConfig($defaultConfig);
            $configWriter->write(self::getConfigName());
        }

        $config = new \Zend_Config_Xml(self::getConfigName(), null, array('allowModifications' => true));
        $config->librato->installed = 1;

        $configWriter = new \Zend_Config_Writer_Xml();
        $configWriter->setConfig($config);
        $configWriter->write(self::getConfigName());

        if (self::isInstalled()) {
            return "Successfully installed.";
        } else {
            return "Could not be installed";
        }
    }

    /**
     * Uninstall plugin
     *
     * Sets config file parameter "installed" to 0 (if config file exists)
     *
     * @return string uninstall success|failure message
     */
    public static function uninstall()
    {
        if (file_exists(self::getConfigName())) {

            $config = new \Zend_Config_Xml(self::getConfigName(), null, array('allowModifications' => true));
            $config->librato->installed = 0;

            $configWriter = new \Zend_Config_Writer_Xml();
            $configWriter->setConfig($config);
            $configWriter->write(self::getConfigName());
        }

        if (!self::isInstalled()) {
            return "Successfully uninstalled.";
        } else {
            return "Could not be uninstalled";
        }
    }

    /**
     * Determine plugin install state
     *
     * @return bool true if plugin is installed (option "installed" is "1" in config file)
     */
    public static function isInstalled()
    {
        if (!file_exists(self::getConfigName())) {
            return false;
        }

        $config = new \Zend_Config_Xml(self::getConfigName());
        if ($config->librato->installed != 1) {
            return false;
        }
        return true;
    }

    /**
     * Return config file name
     *
     * @return string xml config filename
     */
    private static function getConfigName()
    {
        return PIMCORE_WEBSITE_PATH . self::CONFIG_XML;
    }

    public static function &getClient()
    {
        if (self::$libratoClient === null) {

            self::$libratoClient = self::createClient();
        }

        return self::$libratoClient;
    }

    private static function createClient()
    {
        if (!self::isInstalled()) {
            return new Client(); // return dummy client
        }

        $config = new \Zend_Config_Xml(self::getConfigName());

        self::$isEnabled = ($config->librato->get('enabled', '0') == '1');
        if (!self::$isEnabled) {
            return new Client(); // return dummy client
        }

        $email = $config->librato->get('email', '');
        $token = $config->librato->get('token', '');
        $source = $config->librato->get('source', '');

        if ($source == '') {
            $config = \Pimcore\Config::getSystemConfig()->toArray();
            $source = $config["database"]["params"]["dbname"];
        }

        $client = new Client($email, $token);
        $client->setSource($source);
    }

    /**
     * @return int
     */
    public static function getSampleRandomMetric() {
        return rand(1,100);
    }

}

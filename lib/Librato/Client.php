<?php
/**
 * Librato CLient
 */

namespace Librato;

/**
 * Class Client
 *
 * @package Librato
 */
class Client {

    private $libratoApiUrl = "https://metrics-api.librato.com/v1/metrics";

    private $user;

    private $token;

    private $metrics;

    private $opts;

    /**
     * @param $user string the librato email user
     * @param $token string the librato token
     */
    public function __construct($user='', $token='', $opts = array())
    {
        $this->user = $user;
        $this->token = $token;
        $this->opts = $opts;

        $this->resetMetrics();
    }

    /**
     * Sets the source name
     */
    public function setSource($source)
    {
        $this->opts['source'] = $source;
    }

    /**
     * Flushes the buffer on destroy
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * Adds a addGauge to the metrics buffer
     * @param $name string the metric name (Librato style)
     * @param $value int|float the metric value
     * @param array $opts key/values - additional metric options
     */
    public function addGauge($name, $value, array $opts = [])
    {
        $this->addMetric('gauges', $name, $value, $opts);
    }

    /**
     * Adds a addCounter to the metrics buffer
     * @param $name string the metric name (Librato style)
     * @param $value int|float the metric value
     * @param array $opts key/values - additional metric options
     */
    public function addCounter($name, $value, array $opts = [])
    {
        $this->addMetric('counters', $name, $value, $opts);
    }

    /**
     * Adds a metric to the buffer
     * @param $type string addCounter|addGauge
     * @param $name string the metric name (Librato style)
     * @param $value int|float the metric value
     * @param array $opts key/values - additional metric options
     */
    private function addMetric($type, $name, $value, array $opts = [])
    {
        $this->metrics[$type][] = 
            array_merge(
                $this->opts,
                $opts,
                ['name' => $name, 'value' => $value]
            );
    }

    /**
     * Sends metrics to the librato server (API call)
     */
    public function flush()
    {
        if ($this->user == '') return;
        if ($this->token == '') return;

        $ch = curl_init($this->libratoApiUrl);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->metrics));
        curl_setopt($ch, CURLOPT_USERPWD, sprintf("%s:%s", $this->user, $this->token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        // @todo: check status $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->resetMetrics();
    }

    /**
     * Gets metrics currently in the untransmitted buffer
     * @return mixed
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * Clears metrics transferred to the server
     */
    protected function resetMetrics()
    {
        $this->metrics = ['gauges' => [], 'counters' => []];
    }
}


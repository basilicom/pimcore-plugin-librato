Librato Pimcore Plugin
================================================

[![Codacy Badge](https://www.codacy.com/project/badge/40951ff3e29e481381b9b4826cafcf89)](https://www.codacy.com/app/basilicom/pimcore-plugin-librato)
[![Dependency Status](https://www.versioneye.com/php/basilicom-pimcore-plugin:librato/1.0.0/badge.svg)](https://www.versioneye.com/php/basilicom-pimcore-plugin:librato/1.0.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/basilicom/pimcore-plugin-librato/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/basilicom/pimcore-plugin-librato/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/basilicom/pimcore-plugin-librato/badges/build.png?b=master)](https://scrutinizer-ci.com/g/basilicom/pimcore-plugin-librato/build-status/master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/51ed30d0-409a-49e2-9338-e6a63901162e/mini.png)](https://insight.sensiolabs.com/projects/51ed30d0-409a-49e2-9338-e6a63901162e)

Developer info: [Pimcore at basilicom](http://basilicom.de/en/pimcore)

## Synopsis

This Pimcore http://www.pimcore.org plugin simplifies recording
stats and metrics to the Librato service.

## Code Example / Method of Operation

If installed and enabled, the plugin hooks into the maintenance
process/script of pimcore (via the "system.maintenance" event) and
sends custom metric values every five minutes to the Librato servers.

Metrics can be defined via SQL queries and/or static methods.  

## Motivation

Monitoring business values is a crucial part of every application.
Librato is a powerful external service for recording metrics and
analyzing stats. This plugin makes Pimcore integration easy by
using a simple config file and hooking into the maintenance/cronjob
process.

## Installation

Add "basilicom-pimcore-plugin/librato" as a requirement to the
composer.json in the toplevel directory of your Pimcore installation.

Example:

    {
        "require": {
            "basilicom-pimcore-plugin/librato": "~1.0"
        }
    }
    
Install the plugin via the Pimcore Extension Manager. 

Press the "Configure" button of the Librato plugin from within the 
Extension Manager and set the "email" and "token" properties to the
values from your Librato account ( https://metrics.librato.com/account/api_tokens ).

The current Pimcore database name is used as default metric source. 
Use the "source" property to override this.   

Define some metrics. These examples cover all the possiblities:

    <metrics>
        <metric><type>counter</type><name>object_cnt</name><sql>select count(*) as cnt from `objects`</sql></metric>
        <metric><type>counter</type><name>version_cnt</name><sql>select count(*) as cnt from `versions`</sql></metric>
        <metric><type>gauge</type><name>php_sample_method</name><php>\Librato\Plugin::getSampleRandomMetric</php></metric>
    </metrics>
    
Please note: The SQL queries and php static methods must return exactly one numerical value suitable
for Librato API consumption. Types "counter" and "gauge" are supported.

Change the "enabled" property to "1" to enable sending of values.

## API Reference

If you want to send metrics to librato not on the maintenance run, but manually - use the
following methods:

* &\Librato\Plugin::getClient() returns a \Librato\Client configured and ready for sending metrics. Returns a dummy client if plugin is not configured/enabled.
* \Librato\Client->addGauge(string $name, int|float $value) - adds a gauge metric
* \Librato\Client->addCounter(string $name, int|float $value)  - adds a counter metric
* \Librato\Client->flush() - sends metrics buffer to Librato

## Tests

* none

## Todo

* Implement a simple Pimcore Dashboard Widget for Librato integration

## Contributors

* Christoph Luehr <christoph.luehr@basilicom.de>

## License

* BSD-3-Clause

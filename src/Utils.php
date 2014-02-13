<?php

use Symfony\Component\Yaml\Yaml;

class Utils {

    public static $db;
    public static $data;
    public static $couchdb;
    public static $couch;
    public static $strings;

    public static function init() {
        self::config();
        self::$data = __DIR__.'/../data';
        self::$couch = "http://".COUCHDB_HOST.":".COUCHDB_PORT."/".COUCHDB_BASE;
        if(defined('COUCHDB_AUTH')) {
            self::$couchdb = new Chill\Client(COUCHDB_AUTH."@".COUCHDB_HOST.":".COUCHDB_PORT,COUCHDB_BASE);
        } else {
            self::$couchdb = new Chill\Client(COUCHDB_HOST.":".COUCHDB_PORT,COUCHDB_BASE);
        }
        self::$strings = json_decode(file_get_contents(__DIR__."/../resources/locales/".LANG.".json"));
    }

    public static function config() {
        $data = array();

        $array = Yaml::parse(__DIR__."/../resources/config.yml");
        foreach($array as $key=>$value) {
            $data[strtoupper($key)] = $value;
        }

        if(isset($data['ETCD'])) {
            $keys = json_decode( file_get_contents($data['ETCD']."/v2/keys/?recursive=true") );
            foreach($keys->node->nodes as $node) {
                if(isset($node->nodes)) {
                    foreach($node->nodes as $entry) {
                        $key  = strtoupper(str_replace("/","_",substr($entry->key,1)));
                        if(isset($entry->value) && !is_null($entry->value)) {
                            $data[$key] = $entry->value;
                        }
                    }
                }
            }
        }

        foreach($data as $k=>$v) {
            if(strlen($v) >= 1) {
                if(!defined($k)) {
                    define($k,$v);
                }
            }
        }

        return $data;
    }

    public static function schema() {
        $ddoc_json = file_get_contents(Utils::$couch.'/_design/bibliography');
        $ddoc = json_decode($ddoc_json);
        $schema_json = substr( $ddoc->schema->biblio,23,-2);
        $schema = json_decode($schema_json);
        unset($schema->properties->metadata);
        unset($schema->required);
        return $schema;
    }

}

Utils::init();


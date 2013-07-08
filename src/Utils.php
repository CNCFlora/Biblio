<?php


class Utils {

    public static $db;
    public static $data;
    public static $couchdb;
    public static $couch;
    public static $strings;

    public static function init() {
        self::config();
        self::$data = __DIR__.'/../data';
        self::$couch = "http://".COUCH_HOST."/".COUCH_BASE;
        self::$couchdb = new Chill\Client(COUCH_AUTH."@".COUCH_HOST,COUCH_BASE);
        self::$strings = json_decode(file_get_contents(__DIR__."/../resources/locales/".LANG.".json"));
    }

    public static function config() {
        $ini = parse_ini_file(__DIR__."/../resources/config.ini");
        $data = array();
        foreach($ini as $k=>$v) {
            $data[$k] = $v;
            if(!defined($k)) {
                define($k,$v);
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


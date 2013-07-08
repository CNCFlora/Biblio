<?php

class View implements \Rest\View {

    protected $file ;
    protected $props ;

    public static $defaults ;

    function __construct($file,$props=null) {
        $this->template = file_get_contents( __DIR__."/../resources/templates/".$file );

        if(is_array($props)) $this->props = $props ;
        else if(is_object($props)) $this->props = (array) $props;
        else $this->props = array();

        $iterator = new \DirectoryIterator(__DIR__."/../resources/templates");
        foreach ($iterator as $file) {
            if($file->isFile() && preg_match("/\.html$/",$file->getFilename())) {
                $this->partials[substr( $file->getFilename(),0,-5)] = file_get_contents($file->getPath()."/".$file->getFilename());
            }
        }
    }

    function execute(\Rest\Server $rest) {
        $props = array_merge($rest->getParameters(),$this->props);

        $m = new \Mustache_Engine(array('partials'=>$this->partials));
        $content = $m->render($this->template,$props);

        $content = preg_replace('@=[\'"]/([^\'"]*)[\'"]@','="/'.BASE_PATH.'/\1"',$content);

        $rest->getResponse()->setResponse($content);
        return $rest ;
    }

}

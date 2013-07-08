<?php
session_start();

require '../vendor/autoload.php';
require 'Utils.php';
require 'View.php';

$rest = new Rest\Server($_GET['q']);

$data = Utils::config();
foreach($data as $k=>$v) {
    $rest->setParameter($k,$v);
}

if(($user = $rest->getRequest()->getSession('user')) != null) {
    $rest->setParameter("user",$user);
    $rest->setParameter("logged",true);
} else {
    $rest->setParameter("logged",false);
}

$rest->setParameter('strings',Utils::$strings);

$rest->setAccept(array("*"));

$rest->addMap("GET","/?",function($r) {
    $result = array();
    $query  = null;
    if(isset($_GET["query"])) {
        $query = $r->GetRequest()->getGet("query");
        $q = array(
            "size"=>25,
            "from"=>0,
            "facets"=>new StdClass,
            "query"=> array(
                "constant_score"=> array(
                    "query"=> array(
                        "query_string"=> array(
                            "query"=> $query
                        )
                    )
                )
            )
        );
        $r  = json_decode(file_get_contents(ES."/_search?source=".rawurlencode(json_encode($q))))->hits->hits;
        foreach($r as $rr) {
            $result[] = $rr->_source;
        }
    }
    return new View("index.html",array("result"=>$result,"query"=>$query));
});

$rest->addMap('POST',"/login",function($r) {
    $u = json_decode($r->getRequest()->getBody());
    $r->getRequest()->setSession('user',$u);
    return new Rest\View\JSon($u);
});

$rest->addMap('POST',"/logout",function($r) {
    $r->getRequest()->setSession('user',null);
    return new Rest\View\JSon(null);
});

$rest->addMap("GET","/new",function($r) {
    return new View("new.html");
});

$rest->addMap("POST","/new",function($r) {
    $user = $r->getParameter("user");
    $post = $r->getRequesT()->GetPost();

    $data = new StdClass;

    if(isset($post['tite'])) {
        $data->title = $post['title']; 
    } else if(isset($post['doi'])) {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept: application/vnd.citationstyles.csl+json\r\n"
              )
          );
        $context = stream_context_create($opts);
        $content = file_get_contents("http://dx.doi.org/".$post['doi'],false,$context);
        $res = json_decode($content);
        $data->identifier = array(array("name"=>"doi","id"=>$res->DOI));
        $data->title = $res->title;
        $data->link = array(array("url"=>$res->URL,"anchor"=>$res->title));
        $data->author = arraY();
        foreach($res->author as $a) {
            $name = "";
            $g = explode(" ",$a->given);
            foreach($g as $n) {
                $name .= strtoupper( $n[0] ).".";
            }
            $name .= " ".$a->family;
            $data->author[] = array('name'=>$name);
        }
    }

    $metadata = new StdClass;
    $metadata->status = "open";
    $metadata->valid = true;
    $metadata->contributor = $user->name;
    $metadata->contact = $user->email;
    $metadata->creator = $user->name;
    $metadata->created = time();
    $metadata->modified = time();
    $metadata->modified_date = date('d-m-Y',$metadata->modified);
    $metadata->created_date = date('d-m-Y',$metadata->created);
    $data = array (
        'schema'=>json_encode(Utils::schema()),
        'metadata'=>$metadata,
        'data'=>json_encode($data)
    );

    return new View("new_form.html",$data);
});

$rest->addMap('POST',"/new/save",function($r) {
    $data  = $r->getRequest()->getBody();
    $obj = json_decode($data);
    $user = $r->getParameter("user");

    $metadata = new StdClass;
    $metadata->status = "done";
    $metadata->contributor = $user->name;
    $metadata->contact = $user->email;
    $metadata->creator = $user->name;
    $metadata->created = time();
    $metadata->modified = time();
    $metadata->description = isset( $obj->abstract )?$obj->abstract:"";
    $metadata->title  = isset( $obj->title )?$obj->title:"";
    $metadata->source = "";
    $metadata->type = "biblio";
    $metadata->valid = true;
    $metadata->identifier = "urn:lsid:cncflora.jbrj.gov.br:biblio:".(isset($obj->type)?$obj->type:"other" ).":".uniqid();

    $doc = new Chill\Document(Utils::$couchdb);
    $doc->metadata = $metadata;
    $doc->_id = $metadata->identifier;

    foreach($obj as $k=>$v) {
        $doc->$k = $v;
    }

    $doc->save();

    $obj->metadata = $metadata;
    $obj->_id = $metadata->identifier;

    return new \Rest\View\JSon($obj);
    //return new \Rest\Controller\Redirect("/".BASE_APP."/biblio/".$id);
});

$rest->addMap("GET","/authors",function($r) {
    $authors=  array();
    $docs = Utils::$couchdb->getView("bibliography","by_author",null,array("reduce"=>true,"group"=>true));
    foreach($docs['rows'] as $r) {
        $authors[] = array('author'=> $r['key'] ,'count'=>$r['value']);
    }
    return new View("authors.html",array('authors'=>$authors));
});

$rest->addMap("GET","/author/:author",function($r) {
    $author = rawurlencode($r->getRequest()->getParameteR("author"));
    $biblio =  array();
    $docs = Utils::$couchdb->getView("bibliography","by_author",$author,array("reduce"=>false));
    foreach($docs['rows'] as $r) {
        $r['value']['metadata']['modified_date'] = date("d/m/Y",$r['value']['metadata']['modified']);
        $r['value']['metadata']['created_date'] = date("d/m/Y",$r['value']['metadata']['created']);
        $biblio[] = $r['value'];
    }
    return new View("author.html",array('author'=>urldecode( $author ),'biblio'=>$biblio));
});

$rest->addMap("GET","/biblio/:id",function($r) {
    $doc = Utils::$couchdb->asDocuments()->get($r->getRequesT()->getParameter("id"));
    $metadata = $doc->metadata;
    $metadata['modified_date'] = date("d/m/Y",$metadata['modified']);
    $metadata['created_date'] = date("d/m/Y",$metadata['created']);
    return new View("biblio.html",array('metadata'=>$metadata,'biblio'=>$doc));
});

$rest->addMap("GET","/biblio/:id/edit",function($r) {
    $user = $r->getParameter("user");
    $id   = $r->getRequest()->getParameter("id");

    $biblio = Utils::$couchdb->get($id);

    $meta = $biblio['metadata'];
    $meta['modified_date'] = date('d-m-Y',$meta['modified']);
    $meta['created_date'] = date('d-m-Y',$meta['created']);
    if(strpos($meta['contact'],$user->email) === false) {
        $meta['contributor'] = "[".$user->name.'] ; '.$meta['contributor'];
        $meta['contact'] = "[".$user->email.'] ; '.$meta['contact'];
    }

    unset($biblio->meta);

    $data = array (
        'schema'=>json_encode(Utils::schema()),
        'metadata'=>$meta,
        'data'=>json_encode($biblio),
        'biblio'=>$biblio
    );

    return new View("edit.html",$data);
});

$rest->addMap('POST',"/biblio/:id/delete",function($r) {
    $id   = $r->getRequest()->getParameter("id");
    $biblio = Utils::$couchdb->asDocuments()->get($id);
    Utils::$couchdb->delete($biblio->_id,$biblio->_rev);
    return new \Rest\Controller\Redirect('/'.BASE_PATH."/");
});

$rest->addMap('POST',"/biblio/:id",function($r) {
    $id   = $r->getRequest()->getParameter("id");
    $user = $r->getParameter("user");
    $data  = $r->getRequest()->getBody();
    $obj = json_decode($data);

    $biblio = Utils::$couchdb->asDocuments()->get($id);

    $meta = $biblio->metadata;
    $meta['modified'] = time();
    if(strpos($meta['contact'],$user->email) === false) {
        $meta['contributor'] = $user->name." ; ".$meta['contributor'];
        $meta['contact'] = $user->email.' ; '.$meta['contact'];
    }

    $biblio->metadata = $meta;

    foreach($obj as $k=>$v) {
        $biblio->$k = $v;
    }

    $biblio->save();

    $obj->metadata = $meta;
    $obj->_id = $meta[ 'identifier' ];

    return new \Rest\View\JSon($obj);
    //return new \Rest\Controller\Redirect("/".BASE_APP."/biblio/".$id);
});

$rest->addMap("GET",'.*',function($r) {
    $uri = $r->getRequest()->getURI();
    if(strpos($uri,'resources') === false) return new Rest\Controller\NotFound;
    $file = substr($uri,strpos($uri,'resources'));
    return new Rest\Controller\Redirect("/".BASE_PATH."/".$file);
});

$rest->execute();


<?php

require (__DIR__."/lib/httpful/bootstrap.php");




function postNote($note, $grp){
    global $config;
    $APIKEY = $config['zotero']['apikey'];
    $APIURL = $config['zotero']['apiurl'];
    $ZOTGRP = $grp;

    $uriPostNote = "$APIURL/groups/$ZOTGRP/items";
    $jsonNote = '['.json_encode($note).']';
    echo "Posting note...";
    $resPostNote = Httpful\Request::post($uriPostNote)->addHeader('Authorization', "Bearer $APIKEY")->body($jsonNote)->sendsJson()->send();


    if($resPostNote->code==200 && count(get_object_vars($resPostNote->body->success)) ==1){
        echo "OK\n";
        return true;
    }else{
        echo "Error: Code {$resPostNote->code}\n";
        return false;
    }

}

function postItem($objZotero, $grp){
    global $config;
    $APIKEY = $config['zotero']['apikey'];
    $APIURL = $config['zotero']['apiurl'];
    $ZOTGRP = $grp;

    $uriPostObject = "$APIURL/groups/$ZOTGRP/items";
    $jsonObject = '['.json_encode($objZotero).']';
    echo "Posting object {$objZotero->title}...";
    $resPostObject = Httpful\Request::post($uriPostObject)->addHeader('Authorization', "Bearer $APIKEY")->body($jsonObject)->sendsJson()->send();
    //var_dump($resPostThesis);
    //exit();
    if($resPostObject->code==200 && count(get_object_vars($resPostObject->body->success)) ==1){
        $keyObject = get_object_vars($resPostObject->body->success)[0];
        echo "OK, Key $keyObject\n";
        return $keyObject;
    }

    echo "Error: {$resPostObject->body->failed->{"0"}->message}\n";
    echo "\n";

//    var_dump($resPostThesis->body);
//    var_dump($thesis);
//    exit();
    return null;
}

function putItem($objZotero, $grp, $itemkey){
    global $config;
    $APIKEY = $config['zotero']['apikey'];
    $APIURL = $config['zotero']['apiurl'];
    $ZOTGRP = $grp;

    $uriPutObject = "$APIURL/groups/$ZOTGRP/items/$itemkey";
    $jsonObject = '['.json_encode($objZotero).']';
    echo "Putting object {$objZotero->title}, Key $itemkey...";
    $resPutObject = Httpful\Request::put($uriPutObject)->addHeader('Authorization', "Bearer $APIKEY")->body($jsonObject)->sendsJson()->send();
    //var_dump($resPostThesis);
    //exit();
    if($resPutObject->code==200 && count(get_object_vars($resPutObject->body->success)) ==1){
        echo "OK\n";
        return true;
    }
    print_r($resPutObject);
    echo "Error: {$resPutObject->body->failed->{"0"}->message}\n";
    echo "\n";

//    var_dump($resPostThesis->body);
//    var_dump($thesis);
//    exit();
    return null;
}

function getItem($grp, $itemkey){
    global $config;
    $APIKEY = $config['zotero']['apikey'];
    $APIURL = $config['zotero']['apiurl'];
    $ZOTGRP = $grp;
    $uriGetObject = "$APIURL/groups/$ZOTGRP/items/$itemkey";
    $resGetObject = Httpful\Request::get($uriGetObject)->addHeader('Authorization', "Bearer $APIKEY")->send();
    if($resGetObject->code==404){
        echo "Item not found";
        return false;
    }
    elseif ($resGetObject->code!=200){
        echo "Error getting the item $itemkey of group $grp";
        return false;
    }else{
        return $resGetObject->body;
    }
}

function getZoteroTemplate($tmplname){
    global $config;
    $APIURL = $config['zotero']['apiurl'];

    $uriGetTemplate = "$APIURL/items/new?itemType=$tmplname";
    $resGetTemplate = Httpful\Request::get($uriGetTemplate)->send();
    if($resGetTemplate->code!=200){
        echo "Zotero $tmplname template not loaded";
        die();
    }
    return $resGetTemplate->body;
}

function link_exists($grp, $itemkey, $link){
    global $config;
    echo "Executing link_exists for item $itemkey, link: $link";
    $APIKEY = $config['zotero']['apikey'];
    $APIURL = $config['zotero']['apiurl'];
    $ZOTGRP = $grp;
    $uriGetObject = "$APIURL/groups/$ZOTGRP/items/$itemkey/children";
    $resGetObject = Httpful\Request::get($uriGetObject)->addHeader('Authorization', "Bearer $APIKEY")->send();
    if($resGetObject->code==404){
        echo "Item not found";
        return false;
    }
    elseif ($resGetObject->code!=200){
        echo "Error getting the item $itemkey of group $grp";
        return false;
    }else{
        $objChildren = $resGetObject->body;
        foreach ($objChildren as $objChild){
            if(!property_exists($objChild->data, 'linkMode'))
                continue;
            if($objChild->data->linkMode != "linked_url")
                continue;
            if(!property_exists($objChild->data, 'url'))
                continue;
            if($objChild->data->url == $link)
                return true;
        }
    }
    return false;
}
#!/usr/bin/php -q
<?php

/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");

require_once('cliargs.php');
require_once('class.colors.php');
require_once('GeoCalc.class.php');

$verbose=2;
$new_counter=-347;

$cliargs= array(
      'auto' => array(
         'short' => 'a',
         'type' => 'switch',
         'description' => "Get all data automatically from Overpass API, will unset file option (not fully implemented yet)",
         'default' => true
         ),
      'osm' => array(
         'short' => 'o',
         'type' => 'required',
         'description' => "The name of the OSM file parse the XML from.",
         'default' => ''
         ),
      'changefile' => array(
         'short' => 'c',
         'type' => 'optional',
         'description' => "The name of the output diff file (with corrections)",
         'default' => ''
         ),
      'skiplocationupdate' => array(
         'short' => 's',
         'type' => 'switch',
         'description' => "Do not modify any lat/lon data, use when source data is wrong (which it is for some locations) about the coordinates, use the map"
         ),
      'format' => array(
         'short' => 't',
         'type' => 'optional',
         'description' => "The name of the output extension (json, geojson, osm).",
         'default' => ''
         )
      );
$osm_template=<<<EOD
<?xml version='1.0' encoding='UTF-8'?>
<osm version='0.6' upload='true' generator='JOSM'>
%s
</osm>
EOD;

$osm_obj_template=<<<EOD
\n<node %s>
%s</node>
EOD;

$osm_tag_template=<<<EOD
<tag k='%s' v='%s' />\n
EOD;

$geojson_template=<<<EOD
{
      "type":"FeatureCollection",
      "generator":"JOSM",
      "features":[
         %s
         ]
}
EOD;

$geojson_obj_template=<<<EOD
{
   "type":"Feature",
      "properties":{
         "ref":"%s",
         "amenity":"bicycle_rental",
         "name":"%s",
         "capacity":"%s",
         "network":"Velo"
      },
      "geometry":{
         "type":"Point",
         "coordinates":[
            %.7f,
            %.7f
            ]
      }
},
EOD;

/* command line errors are thrown hereafter */
$options = cliargs_get_options($cliargs);

if (isset($options['file']) && !isset($options['osm'])) { 
  logtrace(0,sprintf("[%s] - In file mode, also pass osm file.",__METHOD__));
  exit;
}

if (isset($options['auto'])) { $auto  = 1 ; unset($options['file']); } else { unset($auto); }
if (isset($options['osm'])) {  $osmfile  = trim($options['osm']); } else { unset($osmfile); }
if (isset($options['format'])) { $output  = trim($options['format']); } else { unset($output); }
if (isset($options['skiplocationupdate'])) { $skip = true; } else { $skip = false; }
if (isset($options['changefile'])) { $changefile = trim($options['changefile']); } else { unset($changefile); }

if (empty($changefile)) {
    unset($changefile);
}

$cur_dir = realpath(".");

// Curl call for overpass data on <bbox-query s="50.85074309341152" w="4.3433332443237305" n="50.85677795627684" e="4.357656240463256"/>

// curl 'http://overpass-api.de/api/interpreter' -H 'Pragma: no-cache' -H 'Origin: http://overpass-turbo.eu' -H 'Accept-Encoding: gzip, deflate' -H 'Accept-Language: en,en-US;q=0.8,nl;q=0.6,af;q=0.4,fr;q=0.2' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36' -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'Accept: */*' -H 'Cache-Control: no-cache' -H 'X-Requested-With: overpass-turbo' -H 'Connection: keep-alive' -H 'Referer: http://overpass-turbo.eu/' --data $'data=%3C%3Fxml+version%3D%221.0%22+encoding%3D%22UTF-8%22%3F%3E%3C!--%0ALooks+for+streets+and+ways+with+dash+in+either+addr%3Astreet+or+the+name+%2C+chances+are+it\'s+an+NL%2FFR+value%0AThey+need+to+have+space+in+front+and+after+%2C+if+not+it\'s+probably+a+dash+in+the+streetname+itself.%0A--%3E%0A%0A%3Cosm-script+output%3D%22xml%22%3E%0A++%3Cunion%3E%0A++%3Cquery+type%3D%22way%22%3E%0A++++%3Chas-kv+k%3D%22addr%3Astreet%22+regv%3D%22+-+%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3Cquery+type%3D%22node%22%3E%0A++++%3Chas-kv+k%3D%22addr%3Astreet%22+regv%3D%22+-+%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3Cquery+type%3D%22way%22%3E%0A++++%3Chas-kv+k%3D%22name%22+regv%3D%22+-+%22%2F%3E%0A++++%3Chas-kv+k%3D%22highway%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3C%2Funion%3E%0A++%3Cprint+mode%3D%22meta%22%2F%3E%0A++%3Crecurse+type%3D%22down%22%2F%3E%0A++%3Cprint+mode%3D%22meta%22%2F%3E%0A%3C%2Fosm-script%3E' --compressed

// bbox needs implementation

if (isset($options['auto']))  {
    /* Now load the Overpass XML */
    logtrace(2,sprintf("[%s] - Auto mode (at your risk)",__METHOD__));
    $ch = curl_init();

    $settings= array(
            'curl_connecttimeout' => '10',
            'curl_connecttimeout_ms' => '10000',
            'api_url' => "http://overpass-api.de/api/interpreter?data=%3C%3Fxml+version%3D%221.0%22+encoding%3D%22UTF-8%22%3F%3E%3C!--%0ALooks+for+streets+and+ways+with+dash+in+either+addr%3Astreet+or+the+name+%2C+chances+are+it's+an+NL%2FFR+value%0AThey+need+to+have+space+in+front+and+after+%2C+if+not+it's+probably+a+dash+in+the+streetname+itself.%0A--%3E%0A%0A%3Cosm-script+output%3D%22xml%22%3E%0A++%3Cunion%3E%0A++%3Cquery+type%3D%22way%22%3E%0A++++%3Chas-kv+k%3D%22addr%3Astreet%22+regv%3D%22+-+%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3Cquery+type%3D%22node%22%3E%0A++++%3Chas-kv+k%3D%22addr%3Astreet%22+regv%3D%22+-+%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3Cquery+type%3D%22way%22%3E%0A++++%3Chas-kv+k%3D%22name%22+regv%3D%22+-+%22%2F%3E%0A++++%3Chas-kv+k%3D%22highway%22%2F%3E%0A++++%3Cbbox-query+s%3D%2250.85074309341152%22+w%3D%224.3433332443237305%22+n%3D%2250.85677795627684%22+e%3D%224.357656240463256%22%2F%3E%0A++%3C%2Fquery%3E%0A++%3C%2Funion%3E%0A++%3Cprint+mode%3D%22meta%22%2F%3E%0A++%3Crecurse+type%3D%22down%22%2F%3E%0A++%3Cprint+mode%3D%22meta%22%2F%3E%0A%3C%2Fosm-script%3E",
            'user_agent_string' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36'
            );
    $ch = curl_init($settings['api_url']);

    $c_options=array(
            CURLOPT_USERAGENT => $settings['user_agent_string'],
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_REFERER => 'http://overpass-turbo.eu/',
            CURLOPT_HTTPHEADER => array('HTTP_ACCEPT_LANGUAGE: UTF-8', 'ACCEPT: application/osm3s+xml, application/xml, application/osm3s, */*','Cache-Control: no-cache','Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_CONNECTTIMEOUT => $settings['curl_connecttimeout'],
            CURLOPT_CONNECTTIMEOUT_MS => $settings['curl_connecttimeout_ms'],
            CURLOPT_POST => 0
    );

    curl_setopt_array($ch , $c_options);

    $server_output = curl_exec($ch);

    //echo print_r($server_output,true);exit;
    logtrace(3, print_r($server_output,true));
    $curlinfo = curl_getinfo($ch);

    if ($curlinfo['http_code'] !== 200 ) {
        die("overpass call failed");
    }

    curl_close($ch);

    $osmfile = 'work_overpass.tmp';

    if (!$handle = fopen($osmfile, 'w')) {
        logtrace(0,sprintf("[%s] - Cannot open file '%s'",__METHOD__,$osmfile));
        exit;
    }

    if (fwrite($handle, $server_output) === FALSE) {
        logtrace(0,sprintf("[%s] - Cannot write file '%s'",__METHOD__,$osmfile));
        exit;
    }

    logtrace(1,sprintf("[%s] - Success, wrote buffer to file '%s'",__METHOD__,$osmfile));
    fclose($handle);

    /* Now load the Overpass XML */
}

if (!file_exists($osmfile)) { die("File $osmfile not found"); }

// Load up JOSM / Overpass xml
logtrace(2,sprintf("[%s] - Loading %s",__METHOD__, $osmfile));
$xml = simplexml_load_file($osmfile);
logtrace(2,sprintf("[%s] - Loading done : %s Mb",__METHOD__, round(filesize($osmfile)/1024/1024)));
logtrace(2,sprintf("[%s] - Decoding ... ",__METHOD__));
$marray=(json_decode(json_encode((array) $xml), 1));
logtrace(2,sprintf("[%s] - Decoding Done",__METHOD__));

// Clear up mem
$xml= null; unset ($xml);
if(gc_enabled()) gc_collect_cycles();

// var_dump(gc_enabled()); 
// ini_set('zend.enable_gc', 0); 
// var_dump(gc_enabled()); 


$marra=$marray['node'];
// print_r($marray['way']);exit;

// Handle single street / addr node situations
logtrace(2,sprintf("[%s] - Check validity of street data",__METHOD__));
if(isset($marray['way']['@attributes'] )) {
    logtrace(2,sprintf("[%s] - fixing street array",__METHOD__));
    $w_arra=array($marray['way']);
} else {
    $w_arra=$marray['way'];
}
logtrace(2,sprintf("[%s] - OK",__METHOD__));

$marray = null ; unset($marray);
if(gc_enabled()) gc_collect_cycles();

$new_nodes=array();
$new_ways=array();

// Extract OSM node information and build information array
logtrace(2,sprintf("[%s] - Extracting xml formatted node data ",__METHOD__));
foreach ($marra as $knode => $node) {
    $node_info=$node['@attributes'];
    $break=0;
    //print_r($node);
    //print_r($node_info);exit;
    if (!empty($node['tag'])) {
        $key= array_value_recursive('k', $node['tag']);
        $val= array_value_recursive('v', $node['tag']);
        if (is_string($key)) {
            $break=1;
            // Only single key/val in this object
            $key=array($key);
            $val=array($val);
            //print_r($key);
            //print_r($val);
        }
        $node_tags=array_combine($key, $val);
        if (empty($node_tags)) {
            //print_r($node);exit;
            logtrace(0,sprintf("[%s] - Error, empty tags, somethings isn't parsing well for node '%s'",__METHOD__,$node_info['id']));
            exit;
        }
        $new_nodes[$node_info['id']]['tags']=$node_tags;
    }
    $new_nodes[$node_info['id']]['info']=$node_info;
}

$marra = null ; unset($marra);
if(gc_enabled()) gc_collect_cycles();
//print_r($node_info);
//print_r($node_tags);

logtrace(2,sprintf("[%s] - Extracting xml formatted way data ",__METHOD__));
foreach ($w_arra as $kway => $way) {
    $way_info=$way['@attributes'];
    if(!isset($way_info)) {
        print_r($way);exit;
    }
/*
    if(isset($way['@attributes'] )) {
        // This is probably a relation
        print_r($way);exit;
        continue;

    } else
*/
    if (!isset($way['tag']) && count($way['nd'])) {
        // No tags on this object but it has nodes , not interesting
        // print_r($way);
        continue;
    }

    $key= array_value_recursive('k', $way['tag']);
    $val= array_value_recursive('v', $way['tag']);
    if (is_string($key)) {
        $break=1;
        // Only single key/val in this object
        $key=array($key);
        $val=array($val);
        //print_r($key);
        //print_r($val);
    }
    $way_tags=array_combine($key, $val);
    $new_ways[$way_info['id']]['tags']=$way_tags;
    $new_ways[$way_info['id']]['info']=$way_info;
}

$w_arra = null ; unset($w_arra);
if(gc_enabled()) gc_collect_cycles();

//print_r($new_nodes);exit;
//print_r($new_ways);exit;

$addresses = array();
// Extract addresses / aka streets from nodes and ways
logtrace(2,sprintf("[%s] - Extracting address data from nodes",__METHOD__));
foreach($new_nodes as $k => $node) {
    if (!isset($node['tags'])) {
        logtrace(4,sprintf("[%s] - skipping empty tag node '%s'",__METHOD__,$node['info']['id']));
        if (empty($node['info']['id'])) {
            print_r($node);
            exit;
        }
        // A bit dirty solution
        continue;
    }
    if (!isset($node['tags']['addr:street']) && isset($node['tags']['highway']) && isset($node['tags']['name'])) {
        switch ($node['tags']['highway']) {
            case 'crossing':
                continue;
                break;
            case 'bus_stop':
                continue;
                break;
            default:
                logtrace(4,sprintf("[%s] - node is street with a name and should not exist: '%s'",__METHOD__,$node['info']['id']));
                print_r($node);
                exit(1); 
                break;
        }
    }
    if (isset($node['tags']['addr:street']) && !isset($node['tags']['highway'])) {
        // node has good address information, we will check this
        logtrace(3,sprintf("[%s] - node has good data with addr:street '%s' - %s",__METHOD__,$node['info']['id'], $node['tags']['addr:street']));
        $addresses[]=$node;
        //print_r($node);
    }

    // echo PHP_EOL;
    if(!isset($node['id']) && isset($node['name'])) { 
    }
}

// Sleep 2 - Laptop getting hot on huge files
sleep(2);

$new_nodes = null ; unset($new_nodes);
if(gc_enabled()) gc_collect_cycles();

$streets = array();
// Extract addresses / aka streets from ways
logtrace(2,sprintf("[%s] - Extracting address data from ways",__METHOD__));
foreach($new_ways as $k => $way) {
    if (!isset($way['tags'])) {
        logtrace(4,sprintf("[%s] - skipping empty tag way '%s'",__METHOD__,$way['info']['id']));
        if (empty($way['info']['id'])) {
            print_r($way);
            exit;
        }
        continue;
    }
    if (!isset($way['tags']['addr:street']) && isset($way['tags']['highway']) && isset($way['tags']['name'])) {
        // way is a street with a name (should exist for ways, we need this)
        logtrace(3,sprintf("[%s] - way has good data with name '%s' - %s",__METHOD__,$way['info']['id'], $way['tags']['name']));
        // print_r($way);sleep(1);
        $streets[]=$way;
    }
    if (isset($way['tags']['addr:street']) && !isset($way['tags']['highway'])) {
        // way is a street with a name (should exist for ways, we need this)
        logtrace(3,sprintf("[%s] - way has good data with addr:street '%s' - %s",__METHOD__,$way['info']['id'], $way['tags']['addr:street']));
        $addresses[]=$way;
    }
}

// Sleep 2 - Laptop getting hot on huge files
sleep(2);

$new_ways = null ; unset($new_ways);
if(gc_enabled()) gc_collect_cycles();

$mod_nodes=array();

logtrace(3,sprintf("[%s] - Have %s streets",__METHOD__,count($streets)));
logtrace(3,sprintf("[%s] - Have %s addresses",__METHOD__,count($addresses)));

if (!$streets) { 
    logtrace(3,sprintf("[%s] - Cant check as we have extracted no streets from xml",__METHOD__));
    exit;
}

// print_r($streets);exit;
// For presentation reasons, quick sorted list:
logtrace(2,sprintf("[%s] - Extracting street list data",__METHOD__));
foreach($streets as $k => $v ) {
    $strt[]=$v['tags']['name'];
}
logtrace(3,sprintf("[%s] - Extracted street list",__METHOD__));
if (isset($strt)) { 
    asort($strt); 
    foreach($strt as $k => $v ) {
        logtrace(3,sprintf("[%s] - street '%s'",__METHOD__,$v));
    }
}

/**
* usort callback
*/
function name_compare($a, $b) {
    $aname = $a['tags']['addr:street'];
    $bname = $b['tags']['addr:street'];
    
    // Returns < 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
    $res = strcmp($aname, $bname);
    if ($res === 0) {
        return 0;
    } else if ($res < 0) {
        return -1;
    } else if ($res > 0) {
        return 1;
    }
}

logtrace(2,sprintf("[%s] - Sorting source addresses ...",__METHOD__));
usort($addresses, "name_compare");


logtrace(2,sprintf("[%s] - Done sorting",__METHOD__));

/*
Array
(
    [tags] => Array
        (
            [highway] => residential
            [name] => Rue de l'Arbre Bénit - Gewijde-Boomstraat
            [name:fr] => Rue de l'Arbre Bénit
            [name:nl] => Gewijde-Boomstraat
            [oneway] => yes
        )

    [info] => Array
        (
            [id] => 4726706
            [timestamp] => 2016-02-27T16:26:12Z
            [uid] => 383309
            [user] => AtonX
            [visible] => true
            [version] => 16
            [changeset] => 37484495
        )

)
*/


logtrace(2,sprintf("[%s] - Validating address data",__METHOD__));
$cnt=0;
foreach($addresses as $k => $node) {
    logtrace(4,sprintf("[%s] - Checking address %s",__METHOD__,$k));
    if(isset($node['info']['id'])) {
        $osm_id=$node['info']['id'];
    } else {
        logtrace(0,sprintf("[%s] - Check input file, there is an empty osm_id which is impossible",__METHOD__));
        exit;
    }
    // echo PHP_EOL;
    //if(!isset($node['id']) && isset($node['name'])) 
        //logtrace(4,sprintf("[%s] - Parsing features ref '%s'",__METHOD__,$node['name']));
    if(isset($osm_id) && isset($node['tags']['addr:street'])) { 
        logtrace(4,sprintf("[%s] - Check if street exists '%s'",__METHOD__,$node['tags']['addr:street']));
        $osm_info = search_street_node($node['tags']['addr:street'], $streets);
        // print_r($streets);
        if (count($osm_info)) {
            logtrace(3,sprintf("[%s] - Found matching OSM data for street (min 1 highway) '%s' [id:%d] vs. '%s' [id:%d]",__METHOD__,$node['tags']['addr:street'], $osm_id, $osm_info['tags']['name'], $osm_info['info']['id']));
        //print_r($osm_info);exit;
            //print_r($osm_info);
            //print_r($node);exit;

            // scan_node($osm_info, $node, $skip);
            // $mod_nodes[]=$osm_info;
/*
            if(is_array($new_node)) {
                $arr[$k]=$new_node;
            }
*/
        } else {
            logtrace(4,sprintf("[%s] - Check if there is lowercase match '%s'",__METHOD__,$node['tags']['addr:street']));
            $osm_info = search_street_node($node['tags']['addr:street'], $streets, false);
            if (count($osm_info)) {
                logtrace(2,sprintf("[%s] - Found lowercase match (case problem) '%s' [id:%d] vs. '%s' [id:%d]. (Fix the spelling)",__METHOD__,$node['tags']['addr:street'], $osm_id, $osm_info['tags']['name'], $osm_info['info']['id']));
            } else {
                logtrace(4,sprintf("[%s] - Deep scanning check (nl, fr) '%s'",__METHOD__,$node['tags']['addr:street']));

//function search_street_node_deep($key, array $arr, $singlename = false, $flipped = false, $lev = false)

                $osm_info = search_street_node_deep($node['tags']['addr:street'], $streets, false , true);
                if (count($osm_info)) {
                    logtrace(2,sprintf("[%s] - Found deep scan match '%s' [id:%d] vs. '%s' [id:%d]. (Fix the order)",__METHOD__,$node['tags']['addr:street'], $osm_id, $osm_info['tags']['name'], $osm_info['info']['id']));
                } else {
                    // Deep scanning for ways that do not contain a dash
                    logtrace(3,sprintf("[%s] - Deep scanning check (single name) '%s'",__METHOD__,$node['tags']['addr:street']));
                    $osm_info = search_street_node_deep($node['tags']['addr:street'], $streets, true);
                    if (count($osm_info)) {
                        logtrace(2,sprintf("[%s] - Found deep scan match '%s' [id:%d] vs. '%s' [id:%d]. (Fix the single name of the street)",__METHOD__,$node['tags']['addr:street'], $osm_id, $osm_info['tags']['name'], $osm_info['info']['id']));
                    } else {
                        logtrace(3,sprintf("[%s] - Deep scanning check (levenshtein distance) '%s'",__METHOD__,$node['tags']['addr:street']));
                        $osm_info = search_street_node_deep($node['tags']['addr:street'], $streets, false, false, true);
                        if (count($osm_info)) {
                            logtrace(2,sprintf("[%s] - Found deep scan match (levenshtein) '%s' [id:%d] vs. '%s' [id:%d]. (Fix the minor spell differences)",__METHOD__,$node['tags']['addr:street'], $osm_id, $osm_info['tags']['name'], $osm_info['info']['id']));
                        } else {
                            logtrace(1,sprintf("[%s] - Verify Osm_id: [id:%s] - Missing matching street name '%s'",__METHOD__,$osm_id, $node['tags']['addr:street']));
                            logtrace(3,sprintf("[%s] - Verify this in JOSM [id:%s] - This address might wrong, or the street isn't loaded or located at the edges of the data: '%s'",__METHOD__,$osm_id, $node['tags']['addr:street']));
                        }       
                    }
                }
            }
            // Add this node to OSM change
            // $new_node=create_node($node);
/*
            if(is_array($new_node)) {
                logtrace(3,sprintf("[%s] - Adding node to OSM %s",__METHOD__,print_r($new_node,true)),'green');
                //print_r($new_node);exit;
                $mod_nodes[]=$new_node;
                //$arr[]=$new_node;
            }
*/
        }
    // print_r($node);exit;
    }
    //logtrace(2,sprintf("[%s] - counter %d",__METHOD__,$cnt));

    if ($cnt % 3000 === 0)  {
        $sleep=200000;
        logtrace(3,sprintf("[%s] - usleeping for %d (counter %d)",__METHOD__,$sleep, $cnt));
        usleep($sleep);
   }
   $cnt++;
}

logtrace(1,sprintf("[%s] - Done",__METHOD__));
exit;

$output="";
// output
foreach($mod_nodes as $k => $node) {
/*  
   [148] => Array
        (
            [tags] => Array
                (
                    [amenity] => bicycle_rental
                    [capacity] => 36
                    [name] => Beatrijslaan
                    [network] => Velo
                    [ref] => 153
                )

            [info] => Array
                (
                    [id] => 2346308570
                    [timestamp] => 2013-06-15T18:14:52Z
                    [uid] => 343052
                    [user] => HenningS
                    [visible] => true
                    [version] => 1
                    [changeset] => 16566499
                    [lat] => 51.2185286
                    [lon] => 4.3857064
                    [action] => modify
                )

        )
    print_r($mod_nodes);exit;
*/
    $nn="";
    $mm="";
    foreach($node['info'] as $kk =>$vv) {
        $nn.=sprintf (" %s='%s'", $kk, (string)$vv);
    }
    //print_r($node['tags']);exit;
    foreach($node['tags'] as $kk =>$vv) {
        $mm.=sprintf($osm_tag_template,$kk,$vv);
    }

    $output.=sprintf($osm_obj_template, $nn, $mm);

    $nn="";
    $mm="";
}

if (!empty($changefile)) {

    if (!$handle = fopen($changefile, 'w')) {
        logtrace(0,sprintf("[%s] - Cannot open output file '%s'",__METHOD__,$changefile));
        exit;
    }

    if (fwrite($handle, sprintf($osm_template,$output)) === FALSE) {
        logtrace(0,sprintf("[%s] - Cannot write output file '%s'",__METHOD__,$changefile));
        exit;
    }

    logtrace(1,sprintf("[%s] - Success, wrote changes to output file '%s'",__METHOD__,$changefile));
    fclose($handle);
} else {
    echo sprintf($osm_template,$output);exit;
}

function array_value_recursive($key, array $arr){
    $val = array();
    array_walk_recursive($arr, function($v, $k) use($key, &$val){
        if($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
}

function search_node($key, array &$arr){
    logtrace(3,sprintf("[%s] - Searching for ref street in ways %s .. ",__METHOD__,$key));
    print_r($key);exit;

    foreach($arr as $nodeid => $info) {
        if (isset($info['tags']['ref'])) {
            if ($info['tags']['ref']==$key) {
                return($info); 
            }
        }
    }
    return array();
}

function search_street_node($key, array &$arr, $case_sensitive = true){
    logtrace(5,sprintf("[%s] - Searching for ref street in street ways %s .. ",__METHOD__,$key));
    // print_r($key);exit;

    foreach($arr as $nodeid => $info) {
        if (isset($info['tags']['name'])) {
            if (!empty($case_sensitive)) {
                if (strcmp($info['tags']['name'],$key)==0) {
                    return($info); 
                }
            } else {
                $a=strtolower($info['tags']['name']);
                $b=strtolower($key);
                if (strcmp($a,$b)==0) {
                    logtrace(4,sprintf("[%s] - md5 check key/name :  %s vs %s ",__METHOD__, md5($a), md5($b)));
                    return($info); 
                }
            }
        }
        /* Check name:fr + name:nl = name */
        if (!empty($info['tags']['name:fr']) && !empty($info['tags']['name:nl'])) {
            $named_combo=sprintf("%s - %s", $info['tags']['name:fr'], $info['tags']['name:nl']);
            if (strcmp($info['tags']['name'],$named_combo)==0) {
                logtrace(4,sprintf("[%s] - Both name:fr and name:nl match the name '%s' (combined at - )",__METHOD__,$named_combo));
            } else {
                logtrace(2,sprintf("[%s] - There is a difference between 'name:fr - name:nl' vs. the name:'%s' <> '%s'",__METHOD__,$named_combo, $info['tags']['name']));
            }
        }
    }
    return array();
}
function search_street_node_deep($key, array &$arr, $singlename = false, $flipped = false, $lev = false){
    logtrace(5,sprintf("[%s] - Searching for name:nl/name:fr street in street ways %s .. ",__METHOD__,$key));
    // print_r($key);exit;
    
    $reverse=0;
    
    $data = preg_split('/ - /', $key, -1, PREG_SPLIT_NO_EMPTY);

    if ($flipped) { 
        if (count($data)==2) {
            // Flip
            $key=join(' - ', array_reverse($data));
            // print_r($data);
            if(strcmp($data[0],$data[1])==0) {
                logtrace(4,sprintf("[%s] - Both languages of addr:street source are the same '%s' (split at - )",__METHOD__,$key));
            } else {
                logtrace(4,sprintf("[%s] - Flipped key %s .. ",__METHOD__,$key));
            }
        }
    }

    foreach($arr as $nodeid => $info) {
        $needle=' - ';
        if ($singlename) { 
            if (!strpos ( $info['tags']['name'], $needle )) {
                $checkname=$info['tags']['name'] . ' - ' . $info['tags']['name'];
            } else  {
                $checkname=$info['tags']['name'];
            }
        } else {
                $checkname=$info['tags']['name'];
        }

        /*
        $checkname=null;
        if (isset($info['tags']['name:nl'])) {
            $reverse++;
        }
        if (isset($info['tags']['name:fr'])) {
            $reverse++;
        }

        if ($reverse==2) {
            if($flipped) {
                $checkname=sprintf("%s - %s", $info['tags']['name:nl'], $info['tags']['name:fr']);
            } else {
                $checkname=sprintf("%s - %s", $info['tags']['name:fr'], $info['tags']['name:nl']);
            }
        } 
        */

        if ($lev) { 
            $levenshtein = Levenshtein($checkname,$key);
            logtrace(5,sprintf("[%s] - Levenshtein distance %d : '%s' vs '%s'",__METHOD__,$levenshtein,$key, $checkname, $key));
            if ($levenshtein < 5) {
                logtrace(4,sprintf("[%s] - (Levenshtein=%d) Minor differences detected between names  : '%s' vs '%s'",__METHOD__,$levenshtein,$key, $checkname, $key));
                return($info); 
            }
        }

        if (strlen($checkname)) {
            logtrace(4,sprintf("[%s] - new checkname '%s'",__METHOD__,$checkname));
            logtrace(4,sprintf("[%s] - checkname vs. key = '%s' vs '%s'",__METHOD__,$checkname, $key));
            if (strcmp($checkname,$key)==0) {
                return($info); 
            }
        }
    }
    return array();
}

function change_node(array &$osm_node, array $changes){
    if (is_array($changes) and count($changes)) {
        $changes['action']='modify';
        //print_r($changes);exit;
        logtrace(4,sprintf("[%s] - Applying changes to OSM.. %d",__METHOD__,count($changes)));
        //print_r($changes);exit;
        foreach ($changes as $k=>$v) {
            if (in_array($k,array('lat','lon','action'))) {
                $osm_node['info'][$k]=$v;
                //$osm_node;
            }
            if (in_array($k,array('ref','name','network','capacity'))) {
                $osm_node['tags'][$k]=$v;
            }
        }
        print_r($changes);
    }
}

function my_chomp(&$string) {
   //$this->debug(__METHOD__, "call",5);
   if (is_array($string)) {
      foreach($string as $i => $val) {
         $endchar = chomp($string[$i]);
      }
   } else {
      $endchar = substr("$string", strlen("$string") - 1, 1);
      $string = substr("$string", 0, -1);
   }
   return $endchar;
}

function logtrace($level,$msg, $fg=null, $bg=null ) {
    global $verbose;
    

    $DateTime=@date('Y-m-d H:i:s', time());

    if ( $level <= $verbose ) {
        $mylvl=NULL;
        switch($level) {
            case 0:
                $mylvl ="error";
                break;
            case 1:
                $mylvl ="core ";
                break;
            case 2:
                $mylvl ="info ";
                break;
            case 3:
                $mylvl ="notic";
                break;
            case 4:
                $mylvl ="verbs";
                break;
            case 5:
                $mylvl ="dtail";
                break;
            default :
                $mylvl ="exec ";
                break;
        }
        // 2008-12-08 15:13:06 [31796] - [1] core    - Changing ID
        //"posix_getpid()=" . posix_getpid() . ", posix_getppid()=" . posix_getppid();
        $content = $DateTime. " [" .  posix_getpid() ."]:[" . $level . "]" . $mylvl . " - " . $msg . "\n";

        if (isset($fg) or isset($bg)){
            echo $content;
            $colors = new Colors();
            echo $colors->getColoredString($content,$fg , $bg);
        } else {
            echo $content;
        }
        // "purple", "yellow" 
        // "red", "black"
        // "cyan"
        $ok=0;
    }
}

?>

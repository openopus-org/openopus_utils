<?
  // slug generator

  function slug ($string, $replace = array(), $delimiter = '-') 
  {
    // https://github.com/phalcon/incubator/blob/master/Library/Phalcon/Utils/Slug.php

    if (!extension_loaded ('iconv')) 
    {
      throw new Exception ('iconv module not loaded');
    }

    // save the old locale and set the new locale to UTF-8
    
    $oldLocale = setlocale (LC_ALL, '0');
    setlocale (LC_ALL, 'en_US.UTF-8');
    $clean = iconv ('UTF-8', 'ASCII//TRANSLIT', $string);
    
    if (!empty($replace))
    {
      $clean = str_replace ((array) $replace, ' ', $clean);
    }

    $clean = preg_replace ("/(^the )/i", '', $clean);
    $clean = preg_replace ("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower ($clean);
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = trim ($clean, $delimiter);

    // revert back to the old locale
    
    setlocale (LC_ALL, $oldLocale);
    return $clean;
  }

  // simple mysql insert

  function mysqlinsert ($mysql, $table, $insert)
  {
    foreach ($insert as $ini => $ins)
    {
      $insert[$ini] = mysqli_real_escape_string ($mysql, $ins);
    }

    $query = "insert into {$table} (". implode (", ", array_keys ($insert)). ") values ('". implode ("','", $insert). "')";
    $query = str_replace ("''", "null", $query);
    
    mysqli_query ($mysql, $query);

    return mysqli_insert_id ($mysql);
  }

  // multiline mysql insert

  function mysqlmultinsert ($mysql, $table, $lines)
  {
    //print_r ([$table, $lines]);
    foreach ($lines as $line)
    {
      foreach ($line as $ini => $ins)
      {
        $insert[$ini] = mysqli_real_escape_string ($mysql, $ins);
      }

      $values[] = "('". implode ("','", $insert). "')";
    }

    $query = "insert ignore into {$table} (". implode (", ", array_keys ($lines[0])). ") values ". implode (", ", $values);
    $query = str_replace ("''", "null", $query);
    
    mysqli_query ($mysql, $query);

    return mysqli_insert_id ($mysql);
  }

  // mysql update

  function mysqlupdate ($mysql, $table, $update, $where)
  {
    foreach ($update as $upa => $ups)
    {
      $set[] = "{$upa}='". mysqli_real_escape_string ($mysql, $ups) . "'";
    }

    foreach ($where as $wha => $whs)
    {
      $wheres[] = "{$wha}='". mysqli_real_escape_string ($mysql, $whs) . "'";
    }

    $query = "update $table set ". implode (", ", $set). " where ". implode (" and ", $wheres);
    $query = str_replace ("''", "null", $query);
    
    mysqli_query ($mysql, $query);

    return mysqli_affected_rows ($mysql);
  }

  // mysql query to assoc array

  function mysqlfetch ($mysql, $query)
  {
    $data = mysqli_query ($mysql, $query, MYSQLI_USE_RESULT);

    if ($data)
    {
      while ($ardata = mysqli_fetch_assoc ($data))
      {
        $r[] = $ardata;
      }

      return (isset ($r) ? $r : false);
    }
    else
    {
      return false;
    }
  }

  // api retrieving

  function apidownparse ($url, $format, $token, $usertoken = "")
  {
    $api = CURL_Internals ($url, false, false, false, $token, $usertoken);

    if ($format == "json")
    {
      return json_decode ($api, true);
    }
    else if ($format == "xml")
    {
      $p = xml_parser_create();
      xml_parse_into_struct ($p, $api, $values, $keys);
      xml_parser_free ($p);

      return $values;
    }
  }

  // conversion mm:ss into secs

  function timetosec ($time)
  {
    $expl = explode (":", $time);

    return ($expl[0] * 60) + $expl[1];
  }

  // basic curl retrieving

  function CURL_Internals ($url, $bust = true, $plusheader, $pluspost, $token, $usertoken = "")
  {
    $ts = time ();
    $ch = curl_init ();

    $fp = fopen (DEBUG, "w");

    $header = array();
    $header[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
    $header[] = 'Cache-Control: max-age=0';
    $header[] = 'Connection: keep-alive';
    $header[] = 'Keep-Alive: 300';
    $header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
    $header[] = 'Accept-Language: en-us,en;q=0.5';
    $header[] = 'Pragma: ';

    if ($plusheader)
    {
      if (is_array ($plusheader))
      {
        $credentials = base64_encode ($plusheader[0].":".$plusheader[1]);
        $header[] = 'Authorization: Basic '. $credentials;
      }
    }
    else if ($token)
    {
      $header[] = 'Authorization: Bearer '. $token;

      if ($usertoken)
      {
          $header[] = 'music-user-token: '. $usertoken;
      }
    }

    if ($bust)
    {
        curl_setopt ($ch, CURLOPT_URL, $url. "?". $ts);
    }
    else
    {
        curl_setopt ($ch, CURLOPT_URL, $url);
    }

    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt ($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_ENCODING, '');
    curl_setopt ($ch, CURLOPT_TIMEOUT, 200);
    //curl_setopt ($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt ($ch, CURLOPT_VERBOSE, false);
    curl_setopt ($ch, CURLOPT_STDERR, $fp);

    if ($pluspost)
    {
      curl_setopt ($ch, CURLOPT_POST, 1);
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $pluspost);
    }

    $api = curl_exec ($ch);

    curl_close ($ch);
    fclose ($fp);

    return $api;
  }

  // api return mode

  function apireturn ($apireturn)
  {
    global $starttime, $timesteps, $timetimings;

    $apireturn["status"]["processingtime"] = (microtime (true) - $starttime);
    $apireturn["status"]["api"] = SOFTWARENAME. "-dyn";
    $apireturn["status"]["version"] = SOFTWAREVERSION;

    if (API_RETURN == "json")
    {
      return json_encode ($apireturn);
    }
    elseif (API_RETURN == "array")
    {
      return (print_r ($apireturn, true));
    }
    elseif (API_RETURN == "serial")
    {
      return serialize ($apireturn);
    }
    elseif (API_RETURN == "xml")
    {
      return xmlrpc_encode ($apireturn);
    }
  }

  // cache saving

  function savecache ($file, $content)
  {
      $filename = WEBDIR. $file;
      $dirname = dirname ($filename);

      if (!is_dir ($dirname))
      {
        mkdir ($dirname, 0777, true);
      }

      if (!NOCACHE)
      {
        $fp = fopen ($filename, "w");
        fwrite ($fp, str_replace (SOFTWARENAME. "-dyn", SOFTWARENAME. "-cache", $content));
        fclose ($fp);
      }

      return $content;
  }

  // transforming an array in a url encoded post

  function arraypost ($array)
  {
    foreach ($array as $ak => $av)
    {
      $post[] = "$ak=". urlencode ($av);
    }

    return implode ("&", $post);
  }

  // keeping only certain keys in arrays

  function arraykeep ($array, $keys)
  {
    $i = 0;

    foreach ($array as $ar)
    {
      foreach ($keys as $key)
      {
        $result[$i][$key] = $ar[$key];
      }

      ++$i;
    }

    return $result;
  }

  // check if all items are present in multi-dimensional array

  function arrayitems ($items, $key, $array)
  {
    foreach ($array as $ar)
    {
      if (in_array ($ar[$key], $items))
      {
        $found[$ar[$key]] = 1;
      }
    }

    return (sizeof ($found) == sizeof ($items));
  }

  // renaming a key from an array

  function arrayrenamekey ($array, $oldkey, $newkey)
  {
    $i = 0;

    foreach ($array as $ar)
    {
      foreach ($ar as $key => $value)
      {
        if ($key == $oldkey) $result[$i][$newkey] = $value;
        else $result[$i][$key] = $value;
      }
      
      ++$i;
    }

    return $result;
  }

  // keeping only certain keys but in value-only format

  function arraykeepvalues ($array, $keys)
  {
    $i = 0;

    foreach ($array as $ar)
    {
      foreach ($keys as $key)
      {
        $result[$i] = $ar[$key];
      }

      ++$i;
    }

    return $result;
  }

  // keeping only certain keys in key=>value format

  function arrayobjtoassoc ($array, $pair)
  {
    foreach ($array as $ar)
    {
      $result[slug ($ar[$pair[0]])] = $ar[$pair[1]];
    }

    return $result;
  }

  // deleting certain keys from arrays

  function arraydelete ($array, $keys)
  {
    foreach ($array as $k => $ar)
    {
      foreach ($keys as $key)
      {
        unset ($array[$k][$key]);
      }
    }

    return $array;
  }

  // delete duplicates from an two-dimensional array, using a key as basis

  function arraydedup ($array, $key)
  {
    foreach ($array as $id => $item)
    {
      foreach ($item as $k => $v)
      {
        if ($k == $key)
        {
          if (in_array ($v, $results))
          {
            unset ($array[$id]);
          }
          $results[] = $v;
        }
      }
    }

    return $array;
  }

  // post field check

  function postcheck ($array, $fields)
  {
    $return = true;

    foreach ($fields as $f)
    {
      if (!isset ($array[$f]))
      {
        $return = false;
      }
      else if (!$array[$f])
      {
        $return = false;
      }
    }

    return $return;
  }

  // create a searchable and comparable string for a given work title

  function worksimplifier ($name, $fulltitle = false)
  { 
    $name = strtolower (preg_replace ('/^(the|le|der|das|die|il|lo) /i', ' ', str_replace ("'", " ", $name)));

    if ($fulltitle) 
    {
      $pattern = '/(\/|\,|\(|\"|\-|\;).*/i';
      return trim (preg_replace ($pattern, '', $name));
    }
    else
    {
      $pattern = '/(\/|\,|\(|\"|\-|\;).*/i';
      $stepone = preg_replace ($pattern, '', $name);
      $pattern = '/ in .\b( (minor|major|sharp major|sharp minor|flat major|flat minor|flat|sharp))?/i';
      return trim (preg_replace ($pattern, '', $stepone));
    }
  }

  // identity check

  function simpleauth ($mysql, $id, $hash)
  {
    $auth = mysqlfetch ($mysql, "select auth from user where id = '{$id}'");

    if (!$auth)
    {
        return false;
    }
    else
    {
        if (md5 (floor ((time() + (60 * 1)) / (60 * 5)). "-". $id. "-". $auth[0]["auth"]) == $hash)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
  }

  // lists 

  function composerlist ($condition, $uid)
  {
    global $mysql;

    $return = [];
    $composers = mysqlfetch ($mysql, "select composer_id from user_composer where user_id='{$uid}' and {$condition} = 1");
    
    foreach ($composers as $comp)
    {
      $return[] = $comp["composer_id"];
    }

    return $return;
  }

  function worklist ($uid)
  {
    global $mysql;

    $return = [];
    $works = mysqlfetch ($mysql, "select work_id from user_work where user_id='{$uid}' and favorite = 1");
    
    foreach ($works as $work)
    {
      $return[] = $work["work_id"];
    }

    return $return;
  }

  function workcomposerlist ($uid)
  {
    global $mysql;

    $return = [];
    $works = mysqlfetch ($mysql, "select distinct composer_id from user_work where user_id='{$uid}' and favorite = 1");
    
    foreach ($works as $work)
    {
      if ($work["composer_id"]) $return[] = $work["composer_id"];
    }

    return $return;
  }

  function playlists ($uid)
  {
    global $mysql;

    $return = [];
    $playlists = mysqlfetch ($mysql, "select id, name, playlist.user_id as owner, playlist_recording.work_id as work_id, recording.composer_name as composer_name, recording.work_title as work_title from playlist, user_playlist, playlist_recording, recording where recording.work_id = playlist_recording.work_id and recording.apple_albumid = playlist_recording.apple_albumid and recording.subset = playlist_recording.subset and user_playlist.user_id='{$uid}' and user_playlist.playlist_id=id and playlist_recording.playlist_id=id order by name asc, playlist.id asc");
    
    foreach ($playlists as $playlist)
    {
      if (!$newplaylists["p:". $playlist["id"]]) $newplaylists["p:". $playlist["id"]] = $playlist;
      if ($playlist["composer_name"])
      {
        $newplaylists["p:". $playlist["id"]]["composers"][] = end (explode (" ", $playlist["composer_name"]));
      }
      else
      {
        $newplaylists["p:". $playlist["id"]]["works"][] = $playlist["work_id"];
      }
    }

    foreach ($newplaylists as $playlist)
    {
      $obworks = [];

      if (sizeof ($playlist["works"])) $obworks = openopusdownparse ("work/list/ids/". implode (",", $playlist["works"]). ".json");
      
      foreach ($playlist["composers"] as $comp)
      {
        if (!in_array ($comp, $obworks["abstract"]))
        {
          $obworks["abstract"]["composers"]["portraits"][] = OPENOPUS_DEFCOMP;
          $obworks["abstract"]["composers"]["names"][] = $comp;
          $obworks["abstract"]["works"]["rows"] += 1;
        }
      }

      $obworks["abstract"]["composers"]["rows"] = sizeof ($obworks["abstract"]["composers"]["names"]);
      $return[] = ["id"=>$playlist["id"],"name"=>$playlist["name"],"owner"=>$playlist["owner"],"summary"=>$obworks["abstract"]];
    }

    return $return;
  }

  // open opus api wrapper

  function openopusdownparse ($url, $post = false)
  {
    $api = CURL_Internals (OPENOPUS. "/". $url, false, false, ($post ? arraypost ($post) : false), false);

    return json_decode ($api, true);
  }

  // absolves single track recordings if most recordings of a work is single track

  function compilationdigest ($apireturn, $force = false, $strict = false)
  {
    $total = sizeof ($apireturn["recordings"]);
    $compilations = array_count_values (array_column ($apireturn["recordings"], "singletrack"))["true"];
    $ratio = $compilations / $total;

    $apireturn["status"]["rows"] = $total;
    $apireturn["status"]["stats"]["singletrack"] = $compilations;
    $apireturn["status"]["stats"]["singletrack_ratio"] = round(100*$ratio,2). "%";

    if ($total >= MIN_COMPIL_UNIVERSE || $force)
    {
      foreach ($apireturn["recordings"] as $key => $rec)
      {
        if ($strict)
        {
          if (($rec["singletrack"] == "true" && $ratio < MIN_COMPIL_RATIO) || any_string (COMPILATION_TERMS, $rec["album_name"]))
          {
            $apireturn["recordings"][$key]["compilation"] = "true";
          }
        }
        else
        {
          if (/*$rec["singletrack"] == "true" && */any_string (COMPILATION_TERMS, $rec["album_name"]))
          {
            $apireturn["recordings"][$key]["compilation"] = "true";
          } 
        }
      }
    }
  
    return $apireturn;
  }

  // prepare performers array

  function performersarray ($array)
  {
    foreach ($array as $ar)
    {
      $return[] = ["name"=>$ar, "role"=>""];
    }

    return ($return);
  }

  // return an array of performers along with their guessed roles

  function allperformers ($array, $rldb, $composer)
  {
    global $apireturn;

    foreach ($array as $art)
    {
      $return[] = Array
        (
          "name" => $art,
          "role" => $rldb[slug ($art)]
        );
    }

    // orchestras (almost always) have conductors

    if (sizeof ($return) == 2)
    {
      foreach ($return as $i => $r)
      {
        if ($r["role"] == "Orchestra")
        {
          if ($return[($i ? 0 : 1)]["role"] != "Orchestra" && $return[($i ? 0 : 1)]["role"] != "Ensemble" && $return[($i ? 0 : 1)]["role"] != "Choir") $return[($i ? 0 : 1)]["role"] = "Conductor";
        }
      }
    }

    // ordering the performers

    $return = orderperformers ($return);

    if (sizeof ($return) == 1)
    {
      if ($return[0]["role"] == "Orchestra") $return[] = ["name"=>$composer, "role"=>"Conductor"];
    }

    return $return;
  }

  // order the performers in the soloist-choir-ensemble-orchestra-conductor order

  function orderperformers ($pfs) 
  {
    foreach ($pfs as $pfr)
    {
      switch ($pfr["role"])
      {
        case "Conductor":
          $pfs_last[] = $pfr;
        break;

        case "Orchestra":
          $pfs_prelast[] = $pfr;
        break;

        case "Ensemble":
        case "Choir":
        case "Chorus":
          $pfs_middle[] = $pfr;
        break;

        default:
          $pfs_first[] = $pfr;
      }
    }

    if (sizeof ($pfs_last) > 1)
    {
      // put additional conductors together with choirs

      $pfs_reallylast = [array_pop ($pfs_last)];

      $pfs_middle = array_merge ((array)$pfs_middle, (array)$pfs_last);
      $pfs_last = $pfs_reallylast;
    }

    if (sizeof ($pfs_prelast) > 1)
    {
      // eliminates additional orchestras

      foreach ($pfs_prelast as $orch)
      {
        if (stristr ($orch["name"], "orchestra"))
        {
          $pfs_filtorch[] = $orch;
        }
      }

      if (sizeof ($pfs_filtorch) > 1)
      {
        $lengths = array_map ('strlen', $pfs_filtorch);

        $pfs_prelast = [max ($pfs_filtorch)];
      }
      else
      {
        $pfs_prelast = $pfs_filtorch;
      }
    }

    return array_merge ((array)$pfs_first, (array)$pfs_middle, (array)$pfs_prelast, (array)$pfs_last);
  }

  // checks if all terms of a search are in a string

  function in_string ($search, $string)
  {
    foreach (explode (" ", $search) as $word)
    {
      if (stripos ("--". slug ($string), "-". slug ($word)) === false)
      {
        return false;
      }
    }

    return true;
  }

  // checks if any term of a search are in a string

  function any_string ($search, $string)
  {
    foreach (explode (" ", $search) as $word)
    {
      if (stripos ("--". slug ($string), "-". slug ($word)) !== false)
      {
        return true;
      }
    }

    return false;
  }
  
  function workslug ($work_title)
  {
    preg_match_all (CATALOGUE_REGEX, $work_title, $titlecatcheck);

    if (sizeof ($titlecatcheck[0]))
    {
      $worksearch = end($titlecatcheck[2]). " ". end($titlecatcheck[8]);

      if (strtolower (end($titlecatcheck[2])) == "op" || strtolower (end($titlecatcheck[2])) == "opus")
      {
        preg_match_all ('/(no\.)( )*([0-9]*)/i', $work_title, $opmatches);
        
        if (sizeof ($opmatches[0]))
        {
          $worksearch .= " ". end ($opmatches[0]);
        }
      }

      return slug ($worksearch);
    }
    else 
    {
      return slug (worksimplifier ($work_title));
    }
  }

  function worktitle ($title, $composer)
  {
    $parts = explode (":", $title);
    $composerln = slug (end (explode (" ", $composer)));

    foreach ($parts as $k => $part)
    {
      if (slug ($part) == $composerln || slug ($part) == slug ($composer))
      {
        unset ($parts[$k]);
      }
    }

    $nname = trim (preg_replace ('/^(( )*( |\,|\(|\'|\"|\-|\;|\:)( )*)/i', '', implode (":", $parts)));

    return $nname;
  }

  function urlcheck ($url)
  {
    $ch = curl_init ();

    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt ($ch, CURLOPT_NOBODY, true);
    curl_setopt ($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 200);

    $response = curl_exec ($ch); 
    $httpCode = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
    
    //print_r ([$url, $response, $httpCode, curl_getinfo ($ch), curl_error($ch)]);

    curl_close ($ch);

    if ($httpCode == 200)
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  function allvalidonly ($urls)
  {
    if (!is_array ($urls)) 
    {
      return [];
    }
    else
    {
      foreach ($urls as $url)
      {
        if (trim ($url) == "")
        {
          return [];
        }
      }

      foreach ($urls as $url)
      {
        if (!urlcheck ($url))
        {
          return [];
        }
      }
  
      return $urls;
    }
  }

  function searcharray ($array, $key, $value)
  {
    return array_keys (array_combine (array_keys ($array), array_column ($array, $key)), $value)[0];
  }

  function indentarray ($array, $keys, $newkey)
  {
    foreach ($array as $key => $ar)
    {
      foreach ($keys as $kv)
      {
        $array[$key][$newkey][$kv] = $ar[$kv];
        unset ($array[$key][$kv]);
      }
    }

    return $array;
  }

  function expandarray ($array, $key, $id, $arrayexpansion)
  {
    foreach ($array as $k => $ar)
    {
      $array[$k][$key] = $arrayexpansion[searcharray ($arrayexpansion, $id, $array[$k][$key])];
    }

    return $array;
  }

  function mediadownload ($url, $folder, $filename, $rotate = false) 
  {
    $dirname = MEDIADIR. "/{$folder}";

    if (!is_dir ($dirname))
    {
      mkdir ($dirname, 0777, true);
    }

    if (file_put_contents ("{$dirname}/{$filename}", file_get_contents ($url)))
    {
      if ($rotate)
      {
        $img = imagecreatefromjpeg ("{$dirname}/{$filename}");
        $rimg = imagerotate ($img, 90, 0);
        imagejpeg ($rimg, "{$dirname}/{$filename}");
      }
      
      return MEDIAURL. "/{$folder}/{$filename}";
    }
    else
    {
      return false;
    }
  }

  function htimetoseconds ($time)
  {
    sscanf ($time, "%d:%d:%d", $hours, $minutes, $seconds);
    return isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
  }

  //// definitions

  // return catalogue number or title in slug, simplified format

  define ("CATALOGUE_REGEX", "/( )*(twv|bwv|wwv|hwv|op|opus|cw|g|d|k|kv|hess|woo|fs|k\.anh|wq|w|sz|kk|s|h|rv|jb ([0-9]+\:)|jw ([a-z]+\/)|(hob\.( *[a-z])+\:))( |\.)*((([0-9]+)([a-z])?)(\:.+)?)/i");

  // likely trashy compilation albums

  define ("COMPILATION_PERFORMERS", "guys rieu volo 2cellos divo");
  define ("COMPILATION_TERMS", "sleep relax babies kids mind lounge essenc essent best dream power glory dinner study concentra dumm amazing focus spirit mind forever mood color colour greatest masterpiece heart landscape magic magnificent hits sucess success". COMPILATION_PERFORMERS);

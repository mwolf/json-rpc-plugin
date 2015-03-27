<?php
/**
 * Description of IJR_Server
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Gina Haeussge <osd@foosel.net>
 * @author Michael Klier <chi@chimeric.de>
 * @author Michael Hamann <michael@content-space.de>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');

// fix when '<?xml' isn't on the very first line
if(isset($HTTP_RAW_POST_DATA)) $HTTP_RAW_POST_DATA = trim($HTTP_RAW_POST_DATA);

/**
 * Increased whenever the API is changed
 */
define('DOKU_JSONRPC_API_VERSION',2);

require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(DOKU_INC.'inc/pluginutils.php');
session_write_close();  //close session

if(plugin_isdisabled('jsonrpc'))
{
    die('JSON-RPC server not enabled');
}

require_once('./IJR_Message.php');
require_once('./IJR_Date.php');
require_once('./IJR_IntrospectionServer.php');
require_once('./IJR_CallbackDefines.php');



class dokuwiki_jsonrpc_server extends IJR_IntrospectionServer {
    var $methods       = array();
    var $public_methods = array();
    private $callbackMethods;
    private $config;

    function checkAuth(){
        global $conf;
        global $USERINFO;

        $this->config = $conf['plugin']['jsonrpc'];

        if($this->config['allow_all'] == 1)
        {
            return true;
        }

        $user   = $_SERVER['REMOTE_USER'];
        $allowed_users = explode(';',$this->config['allowed']);
        $allowed_users = array_map('trim', $allowed_users);
        $allowed_users = array_unique($allowed_users);

        if(in_array($user,$allowed_users))
        {
            return true;
        }
        return false;
    }

    function addCallback($method, $callback, $args, $help, $public=false){
        if($public) $this->public_methods[] = $method;
        return parent::addCallback($method, $callback, $args, $help);
    }


    function call($methodname, $args){
        if(!in_array($methodname,$this->public_methods) && !$this->checkAuth()){
            return new IJR_Error(-32603, 'server error. not authorized to call method "'.$methodname.'".');
        }
        return parent::call($methodname, $args);
    }


    function dokuwiki_jsonrpc_server(){
        $callbackDef = new IJR_CallbackDefines();
        $this->callbackMethods = $callbackDef->getWikiMethods();

        $this->IJR_IntrospectionServer();

        foreach($this->callbackMethods as $key)
        {
            $this->addCallback($key['method'], $key['callback'], $key['args'], $key['help'], $key['public']);
        }
        trigger_event('JSONRPC_CALLBACK_REGISTER', $this);
        
        $this->serve();
    }

    public function rawPage($id,$rev=''){
        if(auth_quickaclcheck($id) < AUTH_READ){
            return new IJR_Error(1, 'You are not allowed to read this page');
        }
        $text = rawWiki($id,$rev);
        if(!$text) {
            $data = array($id);
            return trigger_event('HTML_PAGE_FROMTEMPLATE',$data,'pageTemplate',true);
        } else {
            return $text;
        }
    }

    public function getTitle()
    {
        global $conf;
        return $conf['title'];
    }

    public function appendPage($page, $text, $opt)
    {
       $page_cont = $this->rawPage($page);
       $page_cont = $page_cont."\n".$text;
       return saveWikiText($page, $tmp, $opt);
    }

    public function getAttachment($id){
        $id = cleanID($id);
        if (auth_quickaclcheck(getNS($id).':*') < AUTH_READ)
            return new IJR_Error(1, 'You are not allowed to read this file');

        $file = mediaFN($id);
        if (!@ file_exists($file))
            return new IJR_Error(1, 'The requested file does not exist');

        $data = io_readFile($file, false);
        $base64 = base64_encode($data);
        return $base64;
    }

    public function getAttachmentInfo($id){
        $id = cleanID($id);
        $info = array(
            'lastModified' => 0,
            'size' => 0,
        );

        $file = mediaFN($id);
        if ((auth_quickaclcheck(getNS($id).':*') >= AUTH_READ) && file_exists($file)){
            $info['lastModified'] = new IJR_Date(filemtime($file));
            $info['size'] = filesize($file);
        }

        return $info;
    }

    public function htmlPage($id,$rev=''){
        if(auth_quickaclcheck($id) < AUTH_READ){
            return new IJR_Error(1, 'You are not allowed to read this page');
        }
        return p_wiki_xhtml($id,$rev,false);
    }

    public function htmlPagePart($id,$rev='',$maxHeader=3,$maxItems=3){
        if(auth_quickaclcheck($id) < AUTH_READ){
            return new IJR_Error(1, 'You are not allowed to read this page');
        }
        $title = '';
        $cfg = array('maxHeader'=>$maxHeader,'maxItems'=>$maxItems);
        return p_wiki_xhtml_summary_ext($id,$title,$rev,true,$cfg);
    }

    public function listPages(){
        global $conf;

        $list  = array();
        $pages = file($conf['indexdir'] . '/page.idx');
        $pages = array_filter($pages, 'isVisiblePage');

        foreach(array_keys($pages) as $idx) {
            if(page_exists($pages[$idx])) {
                $perm = auth_quickaclcheck($pages[$idx]);
                if($perm >= AUTH_READ) {
                    $page = array();
                    $page['id'] = trim($pages[$idx]);
                    $page['perms'] = $perm;
                    $page['size'] = @filesize(wikiFN($pages[$idx]));
                    $page['lastModified'] = new IJR_Date(@filemtime(wikiFN($pages[$idx])));
                    $list[] = $page;
                }
            }
        }

        return $list;
    }

    public function readNamespace($ns,$opts){
        global $conf;

        if(!is_array($opts)) $opts=array();

        $ns = cleanID($ns);
        $dir = utf8_encodeFN(str_replace(':', '/', $ns));
        $data = array();
        require_once(DOKU_INC.'inc/search.php');
        $opts['skipacl'] = 0; // no ACL skipping for XMLRPC
        search($data, $conf['datadir'], 'search_allpages', $opts, $dir);
        return $data;
    }

    public function listAttachments($ns, $options = array()) {
        global $conf;
        global $lang;

        $ns = cleanID($ns);
        if (!is_array($options)) $options = array();
        $options['skipacl'] = 0; // no ACL skipping for XMLRPC

        if(auth_quickaclcheck($ns.':*') >= AUTH_READ) {
            $dir = utf8_encodeFN(str_replace(':', '/', $ns));

            $data = array();
            require_once(DOKU_INC.'inc/search.php');
            search($data, $conf['mediadir'], 'search_media', $options, $dir);
            $len = count($data);

            if(!$len) return array();

            for($i=0; $i<$len; $i++) {
                unset($data[$i]['meta']);
                $data[$i]['lastModified'] = new IJR_Date($data[$i]['mtime']);
            }

            return $data;
        } else {
            return new IJR_Error(1, 'You are not allowed to list media files.');
        }
    }

    public function search($searchString)
    {
        require_once(DOKU_INC.'inc/html.php');
        require_once(DOKU_INC.'inc/search.php');
        require_once(DOKU_INC.'inc/fulltext.php');
        require_once(DOKU_INC.'inc/pageutils.php');

        $data = array();
        $result = array();

        $searchStr = cleanID($searchString);
        $data = ft_pageLookup($searchStr);
        foreach($data as $id)
        {
            $ns = getNS($id);
            if($ns){
                $name = shorten(noNS($id), ' ('.$ns.')',30);
            }else{
                $name = $id;
            }
            $result[] = $id;
        }

        $data = ft_pageSearch($searchString, $regex);
        if(count($data))
        {
            foreach($data as $id => $cnt)
            {
                $result[] = $id;
            }
        }
        return $result;
    }

    public function listBackLinks($id){
        require_once(DOKU_INC.'inc/fulltext.php');
        return ft_backlinks($id);
    }

    public function pageInfo($id,$rev=''){
        if(auth_quickaclcheck($id) < AUTH_READ){
            return new IJR_Error(1, 'You are not allowed to read this page');
        }
        $file = wikiFN($id,$rev);
        $time = @filemtime($file);
        if(!$time){
            return new IJR_Error(10, 'The requested page does not exist');
        }

        $info = getRevisionInfo($id, $time, 1024);

        $data = array(
            'name'         => $id,
            'lastModified' => new IJR_Date($time),
            'author'       => (($info['user']) ? $info['user'] : $info['ip']),
            'version'      => $time
        );

        return ($data);
    }

    public function putPage($id, $text, $params) {
        global $TEXT;
        global $lang;
        global $conf;

        $id    = cleanID($id);
        $TEXT  = cleanText($text);
        $sum   = $params['sum'];
        $minor = $params['minor'];

        if(empty($id))
            return new IJR_Error(1, 'Empty page ID');

        if(!page_exists($id) && trim($TEXT) == '' ) {
            return new IJR_ERROR(1, 'Refusing to write an empty new wiki page');
        }

        if(auth_quickaclcheck($id) < AUTH_EDIT)
            return new IJR_Error(1, 'You are not allowed to edit this page');

        if(checklock($id))
            return new IJR_Error(1, 'The page is currently locked');

        if(checkwordblock())
            return new IJR_Error(1, 'Positive wordblock check');

        if(!page_exists($id) && empty($sum)) {
            $sum = $lang['created'];
        }

        if(page_exists($id) && empty($TEXT) && empty($sum)) {
            $sum = $lang['deleted'];
        }

        lock($id);

        saveWikiText($id,$TEXT,$sum,$minor);

        unlock($id);

        // run the indexer if page wasn't indexed yet
        if(!@file_exists(metaFN($id, '.indexed'))) {
            // try to aquire a lock
            $lock = $conf['lockdir'].'/_indexer.lock';
            while(!@mkdir($lock,$conf['dmode'])){
                usleep(50);
                if(time()-@filemtime($lock) > 60*5){
                    // looks like a stale lock - remove it
                    @rmdir($lock);
                }else{
                    return false;
                }
            }
            if(isset($conf['dperm']) && $conf['dperm']) chmod($lock, $conf['dperm']);

            require_once(DOKU_INC.'inc/indexer.php');

            if(!defined('INDEXER_VERSION')){
                define('INDEXER_VERSION', 2);
            }
            // do the work
            idx_addPage($id);

            io_saveFile(metaFN($id,'.indexed'),INDEXER_VERSION);
            @rmdir($lock);
        }

        return 0;
    }

    public function putAttachment($id, $file, $params) {
        global $conf;
        global $lang;

        $auth = auth_quickaclcheck(getNS($id).':*');
        if($auth >= AUTH_UPLOAD) {
            if(!isset($id)) {
                return new IJR_ERROR(1, 'Filename not given.');
            }

            $ftmp = $conf['tmpdir'] . '/' . $id;

            // save temporary file
            @unlink($ftmp);
            $buff = base64_decode($file);
            io_saveFile($ftmp, $buff);

            // get filename
            list($iext, $imime,$dl) = mimetype($id);
            $id = cleanID($id);
            $fn = mediaFN($id);

            // get filetype regexp
            $types = array_keys(getMimeTypes());
            $types = array_map(create_function('$q','return preg_quote($q,"/");'),$types);
            $regex = join('|',$types);

            // because a temp file was created already
            if(preg_match('/\.('.$regex.')$/i',$fn)) {
                //check for overwrite
                $overwrite = @file_exists($fn);
                if($overwrite && (!$params['ow'] || $auth < AUTH_DELETE)) {
                    return new IJR_ERROR(1, $lang['uploadexist'].'1');
                }
                // check for valid content
                @require_once(DOKU_INC.'inc/media.php');
                $ok = media_contentcheck($ftmp, $imime);
                if($ok == -1) {
                    return new IJR_ERROR(1, sprintf($lang['uploadexist'].'2', ".$iext"));
                } elseif($ok == -2) {
                    return new IJR_ERROR(1, $lang['uploadspam']);
                } elseif($ok == -3) {
                    return new IJR_ERROR(1, $lang['uploadxss']);
                }

                // prepare event data
                $data[0] = $ftmp;
                $data[1] = $fn;
                $data[2] = $id;
                $data[3] = $imime;
                $data[4] = $overwrite;

                // trigger event
                require_once(DOKU_INC.'inc/events.php');
                return trigger_event('MEDIA_UPLOAD_FINISH', $data, array($this, '_media_upload_action'), true);

            } else {
                return new IJR_ERROR(1, $lang['uploadwrong']);
            }
        } else {
            return new IJR_ERROR(1, "You don't have permissions to upload files.");
        }
    }

    public function deleteAttachment($id){
        $auth = auth_quickaclcheck(getNS($id).':*');
        if($auth < AUTH_DELETE) return new IJR_ERROR(1, "You don't have permissions to delete files.");
        global $conf;
        global $lang;

        // check for references if needed
        $mediareferences = array();
        if($conf['refcheck']){
            require_once(DOKU_INC.'inc/fulltext.php');
            $mediareferences = ft_mediause($id,$conf['refshow']);
        }

        if(!count($mediareferences)){
            $file = mediaFN($id);
            if(@unlink($file)){
                require_once(DOKU_INC.'inc/changelog.php');
                addMediaLogEntry(time(), $id, DOKU_CHANGE_TYPE_DELETE);
                io_sweepNS($id,'mediadir');
                return 0;
            }
            //something went wrong
               return new IJR_ERROR(1, 'Could not delete file');
        } else {
            return new IJR_ERROR(1, 'File is still referenced');
        }
    }

    public function _media_upload_action($data) {
        global $conf;

        if(is_array($data) && count($data)===5) {
            io_createNamespace($data[2], 'media');
            if(rename($data[0], $data[1])) {
                chmod($data[1], $conf['fmode']);
                media_notify($data[2], $data[1], $data[3]);
                // add a log entry to the media changelog
                require_once(DOKU_INC.'inc/changelog.php');
                if ($data[4]) {
                    addMediaLogEntry(time(), $data[2], DOKU_CHANGE_TYPE_EDIT);
                } else {
                    addMediaLogEntry(time(), $data[2], DOKU_CHANGE_TYPE_CREATE);
                }
                return $data[2];
            } else {
                return new IJR_ERROR(1, 'Upload failed.');
            }
        } else {
            return new IJR_ERROR(1, 'Upload failed.');
        }
    }

    public function aclCheck($id) {
        return auth_quickaclcheck($id);
    }

    public function listLinks($id) {
        if(auth_quickaclcheck($id) < AUTH_READ){
            return new IJR_Error(1, 'You are not allowed to read this page');
        }
        $links = array();

        // resolve page instructions
        $ins   = p_cached_instructions(wikiFN(cleanID($id)));

        // instantiate new Renderer - needed for interwiki links
        include(DOKU_INC.'inc/parser/xhtml.php');
        $Renderer = new Doku_Renderer_xhtml();
        $Renderer->interwiki = getInterwiki();

        // parse parse instructions
        foreach($ins as $in) {
            $link = array();
            switch($in[0]) {
                case 'internallink':
                    $link['type'] = 'local';
                    $link['page'] = $in[1][0];
                    $link['href'] = wl($in[1][0]);
                    array_push($links,$link);
                    break;
                case 'externallink':
                    $link['type'] = 'extern';
                    $link['page'] = $in[1][0];
                    $link['href'] = $in[1][0];
                    array_push($links,$link);
                    break;
                case 'interwikilink':
                    $url = $Renderer->_resolveInterWiki($in[1][2],$in[1][3]);
                    $link['type'] = 'extern';
                    $link['page'] = $url;
                    $link['href'] = $url;
                    array_push($links,$link);
                    break;
            }
        }

        return ($links);
    }

    public function getRecentChanges($timestamp) {
        if(strlen($timestamp) != 10)
            return new IJR_Error(20, 'The provided value is not a valid timestamp');

        require_once(DOKU_INC.'inc/changelog.php');
        require_once(DOKU_INC.'inc/pageutils.php');

        $recents = getRecentsSince($timestamp);

        $changes = array();

        foreach ($recents as $recent) {
            $change = array();
            $change['name']         = $recent['id'];
            $change['lastModified'] = new IJR_Date($recent['date']);
            $change['author']       = $recent['user'];
            $change['version']      = $recent['date'];
            $change['perms']        = $recent['perms'];
            $change['size']         = @filesize(wikiFN($recent['id']));
            array_push($changes, $change);
        }

        if (!empty($changes)) {
            return $changes;
        } else {
            // in case we still have nothing at this point
            return new IJR_Error(30, 'There are no changes in the specified timeframe');
        }
    }

    public function getRecentMediaChanges($timestamp) {
        if(strlen($timestamp) != 10)
            return new IJR_Error(20, 'The provided value is not a valid timestamp');

        require_once(DOKU_INC.'inc/changelog.php');
        require_once(DOKU_INC.'inc/pageutils.php');

        $recents = getRecentsSince($timestamp, null, '', RECENTS_MEDIA_CHANGES);

        $changes = array();

        foreach ($recents as $recent) {
            $change = array();
            $change['name']         = $recent['id'];
            $change['lastModified'] = new IJR_Date($recent['date']);
            $change['author']       = $recent['user'];
            $change['version']      = $recent['date'];
            $change['perms']        = $recent['perms'];
            $change['size']         = @filesize(mediaFN($recent['id']));
            array_push($changes, $change);
        }

        if (!empty($changes)) {
            return $changes;
        } else {
            // in case we still have nothing at this point
            return new IJR_Error(30, 'There are no changes in the specified timeframe');
        }
    }

    public function pageVersions($id, $first,$num=null) {
        global $conf;

        $versions = array();

        if(empty($id))
            return new IJR_Error(1, 'Empty page ID');

        require_once(DOKU_INC.'inc/changelog.php');

        if(is_null($num)){
            $num = $conf['recent'];
        }

        $revisions = getRevisions($id, $first, $num+1);

        if(count($revisions)==0 && $first!=0) {
            $first=0;
            $revisions = getRevisions($id, $first, $num+1);
        }

        if(count($revisions)>0 && $first==0) {
            array_unshift($revisions, '');  // include current revision
            array_pop($revisions);          // remove extra log entry
        }

        $hasNext = false;
        if(count($revisions)>$num) {
            $hasNext = true;
            array_pop($revisions); // remove extra log entry
        }

        if(!empty($revisions)) {
            foreach($revisions as $rev) {
                $file = wikiFN($id,$rev);
                $time = @filemtime($file);
                // we check if the page actually exists, if this is not the
                // case this can lead to less pages being returned than
                // specified via $conf['recent']
                if($time){
                    $info = getRevisionInfo($id, $time, 1024);
                    if(!empty($info)) {
                        $data['user'] = $info['user'];
                        $data['ip']   = $info['ip'];
                        $data['type'] = $info['type'];
                        $data['sum']  = $info['sum'];
                        $data['modified'] = new IJR_Date($info['date']);
                        $data['version'] = $info['date'];
                        array_push($versions, $data);
                    }
                }
            }
            return $versions;
        } else {
            return array();
        }
    }

    public function setLocks($set){
        $locked     = array();
        $lockfail   = array();
        $unlocked   = array();
        $unlockfail = array();

        foreach($set['lock'] as $id){
            if(checklock($id)){
                $lockfail[] = $id;
            }else{
                lock($id);
                $locked[] = $id;
            }
        }

        foreach($set['unlock'] as $id){
            if(unlock($id)){
                $unlocked[] = $id;
            }else{
                $unlockfail[] = $id;
            }
        }

        return array(
            'locked'     => $locked,
            'lockfail'   => $lockfail,
            'unlocked'   => $unlocked,
            'unlockfail' => $unlockfail,
        );
    }

    public function login($user,$pass){
        global $conf;
        global $auth;
        if(!$conf['useacl'])
        {
            return 0;
        }
        if(!$auth)
        {
            return 0;
        }
        if($auth->canDo('external'))
        {
            return $auth->trustExternal($user,$pass,false);
        }
        else
        {
            return auth_login($user,$pass,false,true);
        }
    }
}

$server = new dokuwiki_jsonrpc_server();
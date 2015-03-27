<?php
/**
 * Defines callback functions
 *
 * @author Magnus Wolf mwolf2706@googlemail.com
 */


class IJR_CallbackDefines {

    private $methods = array();
    private $obj = array();

    private function defineWikiMethods()
    {
        $this->obj['method'] = 'dokuwiki.getVersion';
        $this->obj['callback'] = 'getVersion';
        $this->obj['args'] = array('string');
        $this->obj['help'] = 'Returns the running DokuWiki version.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.login';
        $this->obj['callback'] = 'this:login';
        $this->obj['args'] = array('integer','string','string');
        $this->obj['help'] = 'Tries to login with the given credentials and sets auth cookies.';
        $this->methods[] = $this->obj;

	/* Function to create user */
	$this->obj['method'] = 'dokuwiki.createUser';
        $this->obj['callback'] = 'this:createUser';
        $this->obj['args'] = array('string','string','string','string');
        $this->obj['help'] = 'Creates an user, based on the Username and password provided';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.getPagelist';
        $this->obj['callback'] = 'this:readNamespace';
        $this->obj['args'] = array('string','struct');
        $this->obj['help'] = 'List all pages within the given namespace.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.getTime';
        $this->obj['callback'] = 'time';
        $this->obj['args'] = array('int');
        $this->obj['help'] = 'Return the current time at the wiki server.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.getTitle';
        $this->obj['callback'] = 'this:getTitle';
        $this->obj['args'] = array('string');
        $this->obj['help'] = 'Get wiki title.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.appendPage';
        $this->obj['callback'] = 'this:appendPage';
        $this->obj['args'] = array('string', 'string', 'struct');
        $this->obj['help'] = 'Appends text to a Wiki Page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'dokuwiki.setLocks';
        $this->obj['callback'] = 'this:setLocks';
        $this->obj['args'] = array('struct');
        $this->obj['help'] = 'Lock or unlock pages.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPage';
        $this->obj['callback'] = 'this:rawPage';
        $this->obj['args'] = array('string','string');
        $this->obj['help'] = 'Get the raw Wiki text of page, latest version.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageVersion';
        $this->obj['callback'] = 'this:rawPage';
        $this->obj['args'] = array('string', 'string');
        $this->obj['help'] = 'Get the raw Wiki text of page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageHTML';
        $this->obj['callback'] = 'this:htmlPage';
        $this->obj['args'] = array('string','string');
        $this->obj['help'] = 'Return page in rendered HTML, latest version.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageHTMLVersion';
        $this->obj['callback'] = 'this:htmlPage';
        $this->obj['args'] = array('string','string');
        $this->obj['help'] = 'Return page in rendered HTML.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getAllPages';
        $this->obj['callback'] = 'this:listPages';
        $this->obj['args'] = array('struct');
        $this->obj['help'] = 'Returns a list of all pages. The result is an array of utf8 pagenames.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getAttachments';
        $this->obj['callback'] = 'this:listAttachments';
        $this->obj['args'] = array('string', 'struct');
        $this->obj['help'] = 'Returns a list of all media files.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getBackLinks';
        $this->obj['callback'] = 'this:listBackLinks';
        $this->obj['args'] = array('struct','string');
        $this->obj['help'] = 'Returns the pages that link to this page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageInfo';
        $this->obj['callback'] = 'this:pageInfo';
        $this->obj['args'] = array('struct','string');
        $this->obj['help'] = 'Returns a struct with infos about the page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageInfoVersion';
        $this->obj['callback'] = 'this:pageInfo';
        $this->obj['args'] = array('string','string');
        $this->obj['help'] = 'Returns a struct with infos about the page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageVersions';
        $this->obj['callback'] = 'this:pageVersions';
        $this->obj['args'] = array('string','string','string');
        $this->obj['help'] = 'Returns the available revisions of the page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.putPage';
        $this->obj['callback'] = 'this:putPage';
        $this->obj['args'] = array('string', 'string', 'struct');
        $this->obj['help'] = 'Saves a wiki page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.search';
        $this->obj['callback'] = 'this:search';
        $this->obj['args'] = array('string');
        $this->obj['help'] = 'Serches for a string in wiki pages.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.listLinks';
        $this->obj['callback'] = 'this:listLinks';
        $this->obj['args'] = array('struct','string');
        $this->obj['help'] = 'Lists all links contained in a wiki page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getRecentChanges';
        $this->obj['callback'] = 'this:getRecentChanges';
        $this->obj['args'] = array('string');
        $this->obj['help'] = 'Returns a struct about all recent changes since given timestamp.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getRecentMediaChanges';
        $this->obj['callback'] = 'this:getRecentMediaChanges';
        $this->obj['args'] = array('struct');
        $this->obj['help'] = 'Returns a struct about all recent media changes since given timestamp.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.aclCheck';
        $this->obj['callback'] = 'this:aclCheck';
        $this->obj['args'] = array('int', 'string');
        $this->obj['help'] = 'Returns the permissions of a given wiki page.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.putAttachment';
        $this->obj['callback'] = 'this:putAttachment';
        $this->obj['args'] = array('string', 'base64', 'struct');
        $this->obj['help'] = 'Upload a file to the wiki.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.deleteAttachment';
        $this->obj['callback'] = 'this:deleteAttachment';
        $this->obj['args'] = array('int', 'string');
        $this->obj['help'] = 'Delete a file from the wiki.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getAttachment';
        $this->obj['callback'] = 'this:getAttachment';
        $this->obj['args'] = array('base64', 'string');
        $this->obj['help'] = 'Download a file from the wiki.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getAttachmentInfo';
        $this->obj['callback'] = 'this:getAttachmentInfo';
        $this->obj['args'] = array('struct', 'string');
        $this->obj['help'] = 'Returns a struct with infos about the attachment.';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'wiki.getPageHTMLPart';
        $this->obj['callback'] = 'this:htmlPagePart';
        $this->obj['args'] = array('string','string','string','int','int');
        $this->obj['help'] = 'Return parts of a page in rendered HTML, latest version.';
        $this->methods[] = $this->obj;
    }

    private function defineSystemMethods()
    {
        $this->obj['method'] = 'system.methodSignature';
        $this->obj['callback'] = 'this:methodSignature';
        $this->obj['args'] = array('array', 'string');
        $this->obj['help'] = 'Returns an array describing the return type and required parameters of a method';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'system.getCapabilities';
        $this->obj['callback'] = 'this:getCapabilities';
        $this->obj['args'] = array('struct');
        $this->obj['help'] = 'Returns a struct describing the XML-RPC specifications supported by this server';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'system.listMethods';
        $this->obj['callback'] = 'this:listMethods';
        $this->obj['args'] = array('array');
        $this->obj['help'] = 'Returns an array of available methods on this server';
        $this->methods[] = $this->obj;

        $this->obj['method'] = 'system.methodHelp';
        $this->obj['callback'] = 'this:methodHelp';
        $this->obj['args'] = array('string', 'string');
        $this->obj['help'] = 'Returns a documentation string for the specified method';
        $this->methods[] = $this->obj;
    }

    public function getWikiMethods()
    {
        $this->defineWikiMethods();
        return $this->methods;
    }

    public function getSystemMethods()
    {
        $this->defineSystemMethods();
        return $this->methods;
    }
}
?>

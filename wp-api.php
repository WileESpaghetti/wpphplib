<?php

class XmlRpcRequest {
	public $params;
	public $methodName;

	public function __construct($method, $params) {
		$this->methodName = $method;
		$this->params = $params;
	}

	public function getParams() {
		$txt = '<params>';
		foreach ($this->params as $param) {
			$txt .= "<param><value>$param</value></param>";
		}
		$txt .= "</params>";

		return $txt;
	}

	public function __toString() {
		$start = "<?xml version=\"1.0\"?>\n<methodCall>";
		$mname = "<methodName>$this->methodName</methodName>";
		$param = $this->getParams();
		$close = '</methodCall>';
		return $start . $mname . $param . $close;
	}
}

class XmlRpcResponse {

	// @param type SimpleXMLElement
	public function parseArray($arraySimpleXml) {
		$array = array();
		$data = $arraySimpleXml->data;
		foreach($data->value as $item) {
			$array[] = $this->parse($item);
		}

		return $array;
	}

	public function parseStruct($structSimpleXml) {
		$struct = array();
		foreach($structSimpleXml->member as $member) {
			$name = $member->name->__toString();
			$value = $this->parse($member->value);
			$struct[$name] = $value;
		}
		return $struct;
	}

	public function parseScalar($scalarSimpleXml) {
		// we really don't care what type it is, so skip it.
		// using first element because value should really only have 0-1 children
		$scaler = $scalarSimpleXml->count() ?$scalarSimpleXml->children()[0]->__toString():$scalarSimpleXml->__toString();
		return $scaler;
	}

	public function parse($data) {
		if ($data->array) {
			$parsed = $this->parseArray($data->array);
		} else if ($data->struct) {
			$parsed = $this->parseStruct($data->struct);
		} else  {
			$parsed = $this->parseScalar($data);
		}
		return $parsed;
	}

	// $xml is string
	public function __construct($xml) {
		$xml = new SimpleXMLElement($xml);
		if ($xml->params) {
			$data = $xml->params->param->value;
			$this->decoded = $this->parse($data);
		} else {
			// fault
		}
	}
}

class WpXmlRpcConnection {
	public $wpUrl;
	public $user;
	public $pass;

	const XML_RPC_ENDPOINT = '/xmlrpc.php';

	private static $headers = array(
		'Content-Type' => 'text/xml',
		'User-Agent' => 'wpphplib'
	);

	public function __construct($wpRoot, $user, $password) {
		$this->wpUrl = $wpRoot . WpXmlRpcConnection::XML_RPC_ENDPOINT;
		$this->user = $user;
		$this->pass = $password;

        $this->defaultParams = array($this->user, $this->pass);
	}

    function sendRequest($request) {
        $req = curl_init($this->wpUrl);

        curl_setopt($req, CURLOPT_HTTPHEADER, WpXmlRpcConnection::$headers);
        curl_setopt($req, CURLOPT_POST, 1);
        curl_setopt($req, CURLOPT_POSTFIELDS, $request->__toString());
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);

        $xml = curl_exec($req);
        return $xml;
    }

	function makeRequest($method, $params) {
		$encoded = new XmlRpcRequest($method, $params);
        $xml = $this->sendRequest($encoded);
        $response = new XmlRpcResponse($xml);
		return $response;
	}

	/* Users */

	/**
	 * Get the list of blogs that this user is a part of
	 * @return
	 */
	function getUsersBlogs() {
		$method = 'wp.getUsersBlogs';
		$params = array($this->user, $this->pass);
        return $this->makeRequest($method, $params)->decoded;
	}

	/*
	 * FIXME
	 * need to implement the optional parameter "fields"
	 */
	function getUser($blogId, $userId) {
		$method = 'wp.getUser';
		$params = array($blogId, $this->user, $this->pass, $userId);
        return $this->makeRequest($method, $params)->decoded;
	}

	/*
	 * FIXME
	 * need to implement the optional parameter "fields" and "filter"
	 */
	function getUsers($blogId) {
		$method = 'wp.getUsers';
		$params = array($blogId, $this->user, $this->pass);
        return $this->makeRequest($method, $params)->decoded;
	}

	/*
	 * FIXME
	 * need to implement the optional parameter "fields"
	 */
	function getProfile($blogId) {
		$method = 'wp.getProfile';
		$params = array($blogId, $this->user, $this->pass);
        return $this->makeRequest($method, $params)->decoded;
	}

	function getAuthors($blogId) {
		$method = 'wp.getAuthors';
		$params = array($blogId, $this->user, $this->pass);
        return $this->makeRequest($method, $params)->decoded;
	}

    /* Posts */
    function getPost() {}
    function getPosts() {
        $method = 'wp.getAuthors';
        $params = array($blogId, $this->user, $this->pass);
        return $this->makeRequest($method, $params)->decoded;
    }
    function newPost() {}
    function editPost() {}
    function deletePost() {}
    function getPostType() {}
    function getPostTypes() {}
    function getPostFormats() {}
    function getPostStatusList() {}
    function getRevisions() {}
    function restoreRevision() {}
}

class WpUser {
	function __construct($uid, $user, $fname, $lname, $bio, $email, $nick, $nice, $url, $display, $regDate, $roles) {
		$this->user_id = $uid;
		$this->username = $user;
		$this->first_name = $fname;
		$this->last_name = $lname;
		$this->bio = $bio;
		$this->email = $email;
		$this->nickname = $nick;
		$this->nicename = $nice;
		$this->url = $url;
		$this->display_name = $display;
		$this->registered = $regDate;
		$this->roles = $roles;
	}
}

class wpTerm {
    function __construct() {
        $this->term_id = null;
        $this->name = null;
        $this->slug = null;
        $this->term_group = null;
        $this->term_taxonomy_id = null;
        $this->taxonomy = null;
        $this->description = null;
        $this->parent = null;
        $this->count = null;
    }
}

class wpPost {
    function __construct() {
        $this->post_id = null;
        $this->post_title = null;
        $this->post_date = null;
        $this->post_date_gmt = null;
        $this->post_modified = null;
        $this->post_modified_gmt = null;
        $this->post_status = null;
        $this->post_type = null;
        $this->post_format = null;
        $this->post_name = null;
        $this->post_author = null;
        $this->post_password = null;
        $this->post_excerpt = null;
        $this->post_content = null;
        $this->post_parent = null;
        $this->post_mime_type = null;
        $this->link = null;
        $this->guid = null;
        $this->menu_order = null;
        $this->comment_status = null;
        $this->ping_status = null;
        $this->sticky = null;
        //struct post_thumbnail1: See wp.getMediaItem.
        $this->terms = array(); // array of wpTerm
//        array custom_fields
//            struct
//            string id
//            string key
//            string value
//        struct enclosure
//            string url
//            int length
//            string type
    }
}

class WpApi {
	/* Posts */
	function getPost() {}
	function getPosts() {}
	function newPost() {}
	function editPost() {}
	function deletePost() {}
	function getPostType() {}
	function getPostTypes() {}
	function getPostFormats() {}
	function getPostStatusList() {}
	function getRevisions() {}
	function restoreRevision() {}

	/* Taxonomies */
	function getTaxonomy() {}
	function getTaxonomies() {}
	function getTerm() {}
	function getTerms() {}
	function newTerm() {}
	function editTerm() {}
	function deleteTerm() {}

	/* Media */
	function getMediaItem() {}
	function getMediaLibrary() {}
	function uploadFile() {}

	/* Comments */
	function getCommentCount() {}
	function getComment() {}
	function getComments() {}
	function newComment() {}
	function editComment() {}
	function deleteComment() {}
	function getCommentStatusList() {}

	/* Options */
	function getOptions() {}
	function setOptions() {}

	/* Users */
	//function getUsersBlogs() {}
	//function getUser() {}
	//function getUsers() {}
	//function getProfile() {}
	function editProfile() {}
	//function getAuthors() {}

	/* OBSOLETE APIS */

	/*
	 * Categories
	 * use the Taxonomies API instead with taxonomy='category'
	 */
	function getCategories() {}
	function suggestCategories() {}
	function newCategory() {}
	function deleteCategory() {}

	/*
	 * Tags
	 * use Taxonomies instead with taxonomy='post_tag'
	 */
	function getTags() {}

	/*
	 * Pages
	 * use Posts instead with post_type='page'
	 */
	function getPage() {}
	function getPages() {}
	function getPageList() {}
	function newPage() {}
	function editPage() {}
	function deletePage() {}
	function getPageStatusList() {}
	function getPageTemplates() {}
}
<?php

class WpXmlRpcConnection {
    const XML_RPC_ACCESS_POINT = '/xmlrpc.php'; // where to send the POST requests
    private static $headers = array(
        'Content-Type' => 'text/xml',
        'User-Agent' => 'wpphplib'
    );

    public $wpUrl;
    public $user;
    public $pass;

    public function __construct($wpRoot, $user, $password) {
        $this->wpUrl = $wpRoot . WpXmlRpcConnection::XML_RPC_ACCESS_POINT;
        $this->user = $user;
        $this->pass = $password;
    }

    function makeRequest($data) {
        $req = curl_init($this->wpUrl);

        curl_setopt($req, CURLOPT_HTTPHEADER, WpXmlRpcConnection::$headers);
        curl_setopt($req, CURLOPT_POST, 1);
        curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);


        return curl_exec($req);
    }
}
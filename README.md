SapeApi Class
===================

Description
-----------
Simple XML-RPC API Client for SAPE.ru

Require
-------
XML-RPC for PHP: http://sourceforge.net/projects/phpxmlrpc/files/phpxmlrpc/

Examples
-------

Initialize:

	require 'xmlrpc.inc'; // required this library
	require 'SapeApi.class.php';
	
	$user = 'your_login_on_sape.ru';
	$pass = 'your_password_on_sape.ru';

	$sape = new SapeApi();

Authorization:

	$sape->set_debug(0)->connect($user, $pass);

Get xmlrpcresp response:

	/* @var $response xmlrpcresp */
	$response = $connect->query('sape.get_site_pages', 88888, 111)->exec();
	if ($response->faultCode()) {
	  echo '[' . $response->faultCode() . '] ' . $response->faultString();
	  exit;
	}
	/* @var $pages array */
	$pages = php_xmlrpc_decode($response->value());

Get all projects:

	$projects = $sape->query('sape.get_projects')->fetch();

Get 12 projects without removed:

	/* @var $projects array */
	$projects = $sape->query('sape.get_projects', 0, 1, 12)->fetch();

Get project by id:
	/* @var $project array */
	$project = $sape->query('sape.get_project', 123456)->fetch();

Error handling:
	$projects = $sape->query('sape.get_projects')->fetch();
	if ($sape->get_errnum()) {
		echo '[' . $sape->get_errnum() . '] ' . $sape->get_error();
	}

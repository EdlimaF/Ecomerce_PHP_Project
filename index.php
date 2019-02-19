<?php

	session_start();
	require_once("vendor/autoload.php");
	require_once('functions.php');
	

	use \Slim\Slim;
	use \Application\PageAdmin;
	use \Application\Model\User;

	$app = new Slim();

	$app->config('debug', true);

	
	require_once('site.php');
	require('admin.php');
	require_once('admin_users.php');
	require_once('admin_categories.php');
	require_once('admin_products.php');
	require_once('admin_orders.php');

	
	$app->run();
?>
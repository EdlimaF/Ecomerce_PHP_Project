<?php 

	use \Hcode\Model\User;
	use \Hcode\Model\Cart;

	function formatPrice($vlprice)
	{

		if (!$vlprice > 0) $vlprice = 0;

		return number_format((float)$vlprice, 2, ',', '.');

	}

	function checkLogin($inadmin = true)
	{

		return User::checkLogin($inadmin);

	}

	function getUserName()
	{

		$user = User::getFromSession();

		return utf8_decode($user->getdesperson());

	}

	function getSubtotal()
	{

		$cart = Cart::getFromSession();

		$cart->getValues();

		return $cart->getvlsubtotal();
	}

?>



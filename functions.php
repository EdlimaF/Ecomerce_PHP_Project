<?php 

	use \Hcode\Model\User;
	use \Hcode\Model\Cart;

	function formatPrice($vlprice)
	{

		if (isset($vlprice)) {

			return number_format((float)$vlprice, 2, ',', '.');

		} else {

			return '0,00';

		}
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



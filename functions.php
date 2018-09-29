<?php 

	use \Application\Model\User;
	use \Application\Model\Cart;
	use \Application\Model\Category;

	function getMenuPos(){

		$home      = '';
		$products = '';
		$cart      = '';

		$active = 'class=active';

		$pos = $_SESSION['menupos'];

		switch ($pos) {
			case 0:
				$home = $active;
				break;
			case 1:
				$products = $active;
				break;
			case 2:
				$cart = $active;
				break;
			
			default:
					$home = $active;
				break;
		}

		$results[0] = [
			'home'=> $home,
			'products'=> $products,
			'cart'=> $cart
		];

		return $results;
	}

	function formatPrice($vlprice)
	{

		if (!$vlprice > 0) $vlprice = 0;

		return number_format((float)$vlprice, 2, ',', '.');

	}

	function formatDate($date)
	{

		return date('d/m/Y', strtotime($date));

	}

	function checkLogin($inadmin = true)
	{

		return User::checkLogin($inadmin);

	}

	function getUserName()
	{

		$user = User::getFromSession();

		return $user->getdesperson();

	}

	function getUserLogin()
	{

		$user = User::getFromSession();

		return $user->getdeslogin();

	}
	
	function getCartNrQtd()
	{

		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return $totals['nrqtd'];
	}

	function getSubtotal()
	{

		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return formatPrice($totals['vlprice']);
	}

	function getCategories() {

		return Category::listAll();

	}

?>



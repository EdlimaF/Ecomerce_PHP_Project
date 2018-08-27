<?php

	use \Hcode\Page;
	use \Hcode\Model\Category;
	use \Hcode\Model\Product;
	use \Hcode\Model\User;
	use \Hcode\Model\Cart;
	use \Hcode\Model\Address;

	
	$app->get('/', function() {

		$products = Product::listAll();

		$page = new Page();

		$page->setTpl('index', [
			'products'=>Product::checkList($products),
		]);
	});

	
	$app->get('/categories/:idcategory', function($idcategory){
		// Variavel de paginação
		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

		$category = new Category();

		$category->get((int)$idcategory);

		$pagination = $category->getProductsPage($page);

		$pages = [];

		for ($i = 1; $i <= $pagination['pages'] ; $i++) {
			array_push($pages, [
				'link'=>'/categories/' .$category->getidcategory(). '?page=' .$i,
				'page'=>$i
			]);
		}

		$page = new Page();

		$page->setTpl('category', [
			'category'=>$category->getValues(),
			'products'=>$pagination['data'],
			'pages'=>$pages
		]);
	});

	$app->get('/products/:desurl', function($desurl){

		$product = new Product();

		$product->getFromURL($desurl);

		$page = new Page();

		$page->setTpl('product-detail', [
			'product'=>$product->getvalues(),
			'categories'=>$product->getCategories()
		]);
	});

	$app->get('/cart', function(){

		$cart = Cart::getFromSession();

		$page = new Page();

		$page->setTpl('cart', [
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts(),
			'error'=>Cart::getMsgError()			
		]);

	});

	$app->get('/cart/:idproduct/add', function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$cart->addProduct($product, $_GET['qtd']);

		header('Location: /cart');
		exit;
	});

	$app->get('/cart/:idproduct/minus', function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$cart->removeProduct($product);

		header('Location: /cart');
		exit;
	});

	$app->get('/cart/:idproduct/remove', function($idproduct){

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$cart->removeProduct($product, true);

		header('Location: /cart');
		exit;
	});

	$app->post('/cart/freight', function(){

		$cart = Cart::getFromSession();

		$cart->setFreight($_POST['zipcode']);

		header('Location: /cart');
		exit;

	});

	$app->get('/checkout', function(){

		User::verifyLogin(false);

		$cart = Cart::getFromSession();

		$address = new Address();

		$page = new Page();

		$page->setTpl('checkout', [
			'cart'=>$cart->getValues(),
			'address'=>$address->getValues()
		]);
	});

	$app->get('/login', function(){

		$page = new Page;

		$errorReg = User::getErrorRegister();

		if (isset($_SESSION['registerValues']) &&  $errorReg == '') {
			$_SESSION['registerValues'] = NULL;
		}

		$page->setTpl('login', [
			'error'=>User::getError(),
			'errorRegister'=>$errorReg,
			'registerValues'=>isset($_SESSION['registerValues']) ? $_SESSION['registerValues'] : [
				'name'=>'',
				'email'=>'',
				'phone'=>''
			]
		]);

	});

	$app->post('/login', function(){

		try {
			
			User::login($_POST['login'], $_POST['password']);

		} catch (Exception $e) {

			User::setError($e->getMessage());
			
		}

		header('location: /checkout');
		exit;

	});

	$app->get('/logout', function(){

		User::logout();

		header('Location: /login');
		exit;
	});

	$app->post('/register', function(){


		$_SESSION['registerValues'] = $_POST;

		// validação dos campos.
		if (!isset($_POST['name']) || $_POST['name'] == '') {

			User::setErrorRegister('Preencha seu nome');
			header('Location: /login');
			exit;

		} else if(!isset($_POST['email']) || $_POST['email'] == '') { 

			User::setErrorRegister('Preencha seu e-mail');
			header('Location: /login');
			exit;

		} else if(isset($_POST['phone']) && !$_POST['phone'] == '' && !is_numeric($_POST['phone'])) { 

			User::setErrorRegister('Preencha o telefone apenas com numeros');
			header('Location: /login');
			exit;

		} else if(!isset($_POST['password']) || $_POST['password'] == '') { 

			User::setErrorRegister('Preencha sua senha');
			header('Location: /login');
			exit;

		} else if (User::checkLoginExist($_POST['email']) === true) {
			
			User::setErrorRegister('Este endereço de e-mail já está sendo usado por outro usuário.');
			header('Location: /login');
			exit;

		} else {

			$user = new User();

			$user->setData([
				'inadmin'=>0,
				'deslogin'=>$_POST['email'],
				'desperson'=>$_POST['name'],
				'desemail'=>$_POST['email'],
				'despassword'=>$_POST['password'],
				'nrphone'=>$_POST['phone']
			]);

		  $user->save();

			User::login($_POST['email'], $_POST['password']);

			header('Location: /checkout');
			exit;

		}

	});



?>
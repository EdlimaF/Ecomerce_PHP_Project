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

		$qtd = isset($_GET['qtd']) ? $_GET['qtd'] : 1;

		$cart->addProduct($product, $qtd);

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

		User::verifyLogin(false, true);

		$_SESSION['checkout'] = NULL;

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

		User::verifyLogin(false);


		// Verifica se o usuario esta vindo do checkout
		if (isset($_SESSION['checkout']) && $_SESSION['checkout'] == true) {

			$_SESSION['checkout'] = NULL;

			header('Location: /checkout');
			exit;
			
		}

		header('Location: /');
		
		exit;

	});

	$app->get('/logout', function(){

		User::logout();

		header('Location: /');
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

		} else if($_POST['password_c'] != $_POST['password']) { 

			User::setErrorRegister('Confirmação de senha não confere, tente novamente');
			header('Location: /login');
			exit;

		} else if (User::checkEmailExist($_POST['email']) === true) {
			
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


	$app->get('/forgot', function() {

		$page = new Page();

		$page->setTpl('forgot');

	});

	$app->post('/forgot', function() {
		
		$user = User::getForgot($_POST['email'], false);

		header('Location: /forgot/sent');
		exit;

	});

	$app->get('/forgot/sent', function() {

		$page = new Page();

		$page->setTpl('forgot-sent');

	});

	$app->get('/forgot/reset', function() {

		$user = User::validForgotDecrypt($_GET['code']);

		$page = new Page();

		$page->setTpl('forgot-reset', array(
			'name'=>$user['desperson'],
			'code'=>$_GET['code']
		));

	});

	$app->post('/forgot/reset', function() {

		$forgot = User::validForgotDecrypt($_POST['code']);

		User::setForgotUsed($forgot['idrecovery']);

		$user = new User();

		$user->get((int)$forgot['iduser']);

		$password = password_hash($_POST['password'], PASSWORD_BCRYPT,[
			'cost'=>12
		]);

		$user->setPassword($password);

		$page = new Page();

		$page->setTpl('forgot-reset-success');

	});

	$app->get('/profile', function(){

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl('profile', [
			'user'=>$user->getValues(),
			'profileError'=>User::getError(),
			'profileMsg'=>User::getSucess()
		]);

	});

	$app->post('/profile', function(){

		User::verifyLogin(false);

		$user = User::getFromSession();

		// validação dos campos.
		if (!isset($_POST['desperson']) || $_POST['desperson'] == '') {

			User::setError('Preencha seu nome');
			header('Location: /profile');
			exit;

		} else if(!isset($_POST['desemail']) || $_POST['desemail'] == '') { 

			User::setError('Preencha seu e-mail');
			header('Location: /profile');
			exit;

		} else if ($_POST['desemail'] != $user->getdesemail() && User::checkEmailExist($_POST['desemail']) === true) {
			
			User::setError('Este endereço de e-mail já está sendo usado por outro usuário.');
			header('Location: /profile');
			exit;

		} else if(isset($_POST['nrphone']) && !$_POST['nrphone'] == '' && !is_numeric($_POST['nrphone'])) { 

			User::setError('Preencha o telefone apenas com numeros');
			header('Location: /profile');
			exit;
		} else {

			$_POST['inadmin'] = $user->getinadmin();
			$_POST['despassword'] = $user->getdespassword();
			$_POST['deslogin'] = $_POST['desemail'];

			$user->setData($_POST);

			// var_dump($user);
			// exit;

			$user->update();

			User::setSucess('Dados alterados com sucesso!');

			$_SESSION[User::SESSION] = $user->getValues();

			header('Location: /profile');
			exit;
		}
	});





?>
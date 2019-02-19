<?php

	use \Slim\Slim;
	use \Application\Page;
	use \Application\Model\Category;
	use \Application\Model\Product;
	use \Application\Model\User;
	use \Application\Model\Cart;
	use \Application\Model\Address;
	use \Application\Model\Order;
	use \Application\Model\OrderStatus;

	
	
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

	$app->get('/products', function(){

		$_SESSION['menupos'] = 1;

		if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
			$pge = $_GET['page'];
		} else {
			$pge = 1;
		}

		$pagination =  Product::getProductsPage($pge, 10);

		$products = Product::checkList($pagination['data']);

		$pages = (isset($page)) ? $page : [];


		if (count($products) > 0) {

			for ($i = 1; $i <= $pagination['pages'] ; $i++) {
				array_push($pages, [
					'link'=>'/products' .'?page=' .$i,
					'page'=>$i
				]);
			}

		} else if (--$pge > 0) {

			header('Location: /products?page='. --$pge);
				exit;
			
		}

		$page = new Page();

		$page->setTpl('products-list', [
			'products'=>$products,
			'pages'=>$pages,
			'page'=> $pge
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

		$cart->setFreight($_POST['deszipcode']);

		header('Location: /cart');
		exit;

	});

	$app->get('/cart', function(){

		$_SESSION['menupos'] = 2;

		$cart = Cart::getFromSession();

		$page = new Page();

		$page->setTpl('cart', [
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts(),
			'error'=>Cart::getMsgError()			
		]);

	});

	$app->get('/updatecep', function(){

		User::verifyLogin(false, true);

		$address = new Address();

		$cart = Cart::getFromSession();

		$cartzipcode = $cart->getdeszipcode();

		if (isset($_GET['deszipcode']) 
			&& $_GET['deszipcode'] != $cartzipcode 
			&& $_GET['deszipcode'] != '')  {

			$add = Address::getCEP($_GET['deszipcode']);

			if (!isset($add) || isset($add['erro']))  {

				Address::setMsgError('O CEP ('.$_GET['deszipcode'].') não é valido.');
				
			} else {

				$cart->setdeszipcode($_GET['deszipcode']);
				$cart->setToSession();

			}

			header('Location: /checkout');
			exit;
			
		} else if (!isset($_GET['deszipcode']) || $_GET['deszipcode'] == '') {

			Address::setMsgError('O CEP não foi informado');
			
			header('Location: /checkout');
			exit;
			
		} 

	});

	$app->get('/checkout', function(){

		User::verifyLogin(false, true);

		$cart = Cart::getFromSession();

		if (count($cart->getProducts()) > 0) {

			if ($cart->getvlfreight() <= 0) {

				Cart::setMsgError('Calcule o frete antes de continuar.');

				header('Location: /cart');
				exit;
				
			}
			
		} else {

			Cart::setMsgError('Não existe nenhum produto no carrinho.');

			header('Location: /cart');
			exit;

		}

		

		$address = new Address();

		$_GET['deszipcode'] = $cart->getdeszipcode();

		$address->loadFromCEP($_GET['deszipcode']);

		$cart->setdeszipcode($_GET['deszipcode']);

		$cart->save();

		$cart->getCalculateTotal();

		if (!$address->getdesaddress()) $address->setdesaddress('');
		if (!$address->getdesnumber()) $address->setdesnumber('');
		if (!$address->getdecomplement()) $address->setdescomplement('');
		if (!$address->getdesdistrict()) $address->setdesdistrict('');
		if (!$address->getdescity()) $address->setdescity('');
		if (!$address->getdesstate()) $address->setdesstate('');
		if (!$address->getdescountry()) $address->setdescountry('');
		if (!$address->getdeszipcode()) $address->setdeszipcode('');

		$_SESSION['checkout'] = NULL;

		$page = new Page();

		$page->setTpl('checkout', [
			'error'=>Address::getMsgError(),
			'cart'=>$cart->getValues(),
			'address'=>$address->getValues(),
			'products'=>$cart->getProducts()
		]);

	});

	$app->post('/checkout', function(){

		User::verifyLogin(false);

		// validação dos campos.
		if (!isset($_POST['deszipcode']) || $_POST['deszipcode'] === '') {

			Address::setMsgError('Informe o CEP');
			header('Location: /checkout');

			exit;

		} else if(!isset($_POST['desaddress']) || $_POST['desaddress'] === '') { 

			Address::setMsgError('Informe o endereço');
			header('Location: /checkout');
			exit;

		} else if(!isset($_POST['desnumber']) || $_POST['desnumber'] === '') { 

			Address::setMsgError('Informe o número');
			header('Location: /checkout');
			exit;

		} else if(!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') { 

			Address::setMsgError('Informe o bairro');
			header('Location: /checkout');
			exit;

		} else if(!isset($_POST['descity']) || $_POST['descity'] === '') { 

			Address::setMsgError('Informe a cidade');
			header('Location: /checkout');
			exit;

		} else if(!isset($_POST['desstate']) || $_POST['desstate'] === '') { 

			Address::setMsgError('Informe o estado');
			header('Location: /checkout');
			exit;

		} else if(!isset($_POST['descountry']) || $_POST['descountry'] === '') { 

			Address::setMsgError('Informe o país');
			header('Location: /checkout');
			exit;

		} else {

			$user = User::getFromSession();

			$cart = Cart::getFromSession();

			if ($cart->getdeszipcode() != $_POST['deszipcode']) {

				Address::setMsgError('O CEP "'.$_POST['deszipcode'].'" informado está diferente do calculado. clicque "Atualizar CEP" para recalcular o frete.');
				header('Location: /checkout');
				exit;
				
			}

			$cart->getCalculateTotal();

			$order = new Order();

			$order->setValues([
				'iduser'=>$user->getiduser(),
				'idstatus'=>OrderStatus::EM_ABERTO,
				'vlfreight'=>$cart->getvlfreight(),
				'vlsubtotal'=>$cart->getvlsubtotal(),
				'vltotal'=>$cart->getvltotal()
			]);


			if ($order->save()){ // Se o pedido for salvo

				$products = $cart->getProducts(); // adiciona os produtos ao pedido

				foreach ($products as $value) {
					$order->addProduct($value);
				}

				$_POST['idorder'] = $order->getidorder();

				
				$address = new Address(); // salva o endereço associado ao pedido

				$address->setValues($_POST);

				$address->save();

				$cart->delete(); // renova o carrinho da sessão

				unset($_SESSION[Cart::SESSION]);

				$cart = Cart::getFromSession();
			}


			header('Location: /order/'.$_POST['idorder']);
			exit;
		}

	});

	$app->get('/login', function(){

		// Solicitação de instrição vindo do footer.
		$_GET['email'] = (isset($_GET['email']) && $_GET['email'] != '') ? $_GET['email'] : '';

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
				'email'=>$_GET['email'],
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

			$user->setValues([
				'inadmin'=>0,
				'deslogin'=>$_POST['email'],
				'desperson'=>$_POST['name'],
				'desemail'=>$_POST['email'],
				'despassword'=>$_POST['password'],
				'nrphone'=>$_POST['phone']
			]);

		  $user->save();

			User::login($_POST['email'], $_POST['password']);

			// Verifica se o usuario esta vindo do checkout
			if (isset($_SESSION['checkout']) && $_SESSION['checkout'] == true) {

				$_SESSION['checkout'] = NULL;

				header('Location: /checkout');
				exit;
				
			}

			header('Location: /');
			
			exit;

		}

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

			$user->setPassword($_POST['password']);

		$page = new Page();

		$page->setTpl('forgot-reset-success');

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

	$app->get('/profile', function(){

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl('profile', [
			'user'=>$user->getValues(),
			'profileError'=>User::getError(),
			'profileMsg'=>User::getSuccess()
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

			$user->setValues($_POST);

			$user->update();

			User::setSuccess('Dados alterados com sucesso!');

			$_SESSION[User::SESSION] = $user->getValues();

			header('Location: /profile');
			exit;
		}
	});

	$app->get('/order/:idorder', function($idorder){

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		$page = new Page();

		$page->setTpl('payment', [
			'order'=>$order->getValues()
		]);

	});

	$app->get('/boleto/:idorder', function($idorder){

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		// DADOS DO BOLETO PARA O SEU CLIENTE
		$dias_de_prazo_para_pagamento = 10;
		$taxa_boleto = 5.00;
		$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
		$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
		$valor_cobrado = str_replace(",", ".",$valor_cobrado);
		$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

		$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
		$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
		$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
		$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
		$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
		$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

		// DADOS DO SEU CLIENTE
		$dadosboleto["sacado"] = $order->getdesperson();
		$dadosboleto["endereco1"] =  $order->getdesaddress().' '.'N&ordm;'.$order->getdesnumber().' '.$order->getdesdistrict().' '.$order->getdescomplement();
		$dadosboleto["endereco2"] =  $order->getdescity().'-'.$order->getdesstate().'  CEP:'.$order->getdeszipcode().' '.$order->getdecountry();

		// INFORMACOES PARA O CLIENTE
		$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Lojas Lima & Cia. E-commerce";
		$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
		$dadosboleto["demonstrativo3"] = "";
		$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
		$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
		$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: email@mail.com";
		$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Lima & Cia. E-commerce não tem validade é apenas um teste";

		// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
		$dadosboleto["quantidade"] = "";
		$dadosboleto["valor_unitario"] = "";
		$dadosboleto["aceite"] = "";		
		$dadosboleto["especie"] = "R$";
		$dadosboleto["especie_doc"] = "";


		// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


		// DADOS DA SUA CONTA - ITAÚ
		$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
		$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
		$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

		// DADOS PERSONALIZADOS - ITAÚ
		$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

		// SEUS DADOS
		$dadosboleto["identificacao"] = "Lima & Cia.";
		$dadosboleto["cpf_cnpj"] = "00.000.000/0000-00";
		$dadosboleto["endereco"] = "Rua Sem referencia, 000 - desconhecido, 00000-000";
		$dadosboleto["cidade_uf"] = "Aracaju - SE";
		$dadosboleto["cedente"] = "Lima & Cia.";

		// NÃO ALTERAR!
		$path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR .'res'.DIRECTORY_SEPARATOR.'boletophp'.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR;

		require_once($path."funcoes_itau.php"); 
		require_once($path."layout_itau.php");


	});

	$app->get('/profile/orders/:idorder', function($idorder){

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl('profile-orders-detail', [
			'order'=>$order->getValues(),
			'products'=>$order->getProducts($idorder)
		]);

	});


	$app->get('/profile/orders', function(){

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl('profile-orders', [
			'orders'=>$user->getOrders()
		]);

	});
	

	$app->get('/profile/change-password', function(){

		User::verifyLogin(false);

		$page = new page();

		$page->setTpl('profile-change-password', [
			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()
		]);
	});


	$app->post('/profile/change-password', function(){

		User::verifyLogin(false);

		$user = User::getFromSession();

		// validação dos campos.
		if (!isset($_POST['current_pass']) || $_POST['current_pass'] == '') {

			User::setError('Digite a senha atual.');
			header('Location: /profile/change-password');
			exit;

		} else if (!isset($_POST['new_pass']) || $_POST['new_pass'] == '') {

			User::setError('Digite a nova senha.');
			header('Location: /profile/change-password');
			exit;

		} else if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] == '') {

			User::setError('Confirme a nova senha.');
			header('Location: /profile/change-password');
			exit;

		} else if ($_POST['new_pass_confirm'] !== $_POST['new_pass']) {

			User::setError('Confimação da nova senha invalida.');
			header('Location: /profile/change-password');
			exit;

		}  else if ($_POST['new_pass'] == $_POST['current_pass']) {

			User::setError('A sua nova senha deve ser diferente da atual.');
			header('Location: /profile/change-password');
			exit;

		}  else if (!password_verify($_POST['current_pass'], $user->getdespassword())) {

			User::setError('Senha atual não confere.');
			header('Location: /profile/change-password');
			exit;

		} else {


			$user->setPassword($_POST['new_pass']);

			User::login($user->getdeslogin(), $_POST['new_pass']);

			User::setSuccess('Semha alterada com sucesso.');
			header('Location: /profile/change-password');
			exit;
		}

		
	});

	$app->get('/', function() {

		$_SESSION['menupos'] = 0;


		$productsSlide = Product::listAll('dtregister DESC', 'LIMIT 8');
		$products = Product::listAll();
	
		$page = new Page();

	
		$page->setTpl('index', [
			'productsSlide'=>Product::checkList($productsSlide),
			'products'=>Product::checkList($products)
		]);
	});

?>
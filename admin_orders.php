
<?php 

	use \Application\PageAdmin;
	use \Application\Model\User;
	use \Application\Model\Order;
	use \Application\Model\OrderStatus;

	$app->get('/admin/orders/:idorder/status', function($idorder){

		User::verifyLogin();

		$order = new Order();

		$order->get((int)$idorder);

		$page = new PageAdmin();

		$page->setTpl('order-status', [
			'order'=>$order->getValues(),
			'status'=>OrderStatus::listAll(),
			'msgSuccess'=>Order::getSuccess(),
			'msgError'=>Order::getError()
		]);

	});

	$app->post('/admin/orders/:idorder/status', function($idorder){

		User::verifyLogin();

		if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {

			Order::setError('Status incorreto.');
			header('Location: /admin/orders/'.$idorder.'/status');
			exit;

		} else {

			$order = new Order();

			$order->get((int)$idorder);

			$order->setidstatus((int)$_POST['idstatus']);

			$order->save();

			Order::setSuccess('Sataus atualizado com sucesso.');
			header('Location: /admin/orders/'.$idorder.'/status');
			exit;
		}

	});


	$app->get('/admin/orders/:idorder/delete', function($idorder){

		User::verifyLogin();

		$order = new Order();

		$order->get((int)$idorder);

		$order->delete();

		header('Location: /admin/orders');
		exit;

	});

	$app->get('/admin/orders/:idorder', function($idorder){

		User::verifyLogin();

		$order = new Order();

		$order->get((int)$idorder);

		$page = new PageAdmin();

		$page->setTpl('order', [
			'order'=>$order->getValues(),
			'products'=>$order->getProducts($idorder)
		]);

	});

	$app->get('/admin/orders', function(){

		User::verifyLogin();

		$search = (isset($_GET['search'])) ? $_GET['search'] : '';

		$page = (isset($_GET['page'])) ? $_GET['page'] : 1;

		if ($search != '') {
			$pagination = Order::getPageSearch($search, $page);
		} else {
			$pagination = Order::getOrdersPage($page);
		}

		$pages = [];

		for ($i = 1; $i <= $pagination['pages']; $i++) {

			array_push($pages, [
				'href'=>'/admin/orders?'.http_build_query([
					'page'=>$i,
					'search'=>$search
				]),
				'text'=>$i
			]);
			
		}

		$page = new PageAdmin();

		$page->setTpl('orders', [
			'orders'=>$pagination['data'],
			'search'=>$search,
			'pages'=>$pages
		]);

	});





?>
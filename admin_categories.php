<?php 

	use \Application\PageAdmin;
	use \Application\Model\User;
	use \Application\Model\Category;
	use \Application\Model\Product;

	
	$app->get('/admin/categories', function(){

		User::verifyLogin();

		$search = (isset($_GET['search'])) ? $_GET['search'] : '';

		$page = (isset($_GET['page'])) ? $_GET['page'] : 1;

		if ($search != '') {
			$pagination = Category::getPageSearch($search, $page);
		} else {
			$pagination = Category::getCategoriesPage($page);
		}

		$pages = [];

		for ($i = 1; $i <= $pagination['pages']; $i++) {

			array_push($pages, [
				'href'=>'/admin/categories?'.http_build_query([
					'page'=>$i,
					'search'=>$search
				]),
				'text'=>$i
			]);
			
		}

		$page = new PageAdmin();

		$page->setTpl('categories', [
			'categories'=>$pagination['data'],
			'search'=>$search,
			'pages'=>$pages
		]);

	});

	$app->get('/admin/categories/create', function(){

		User::verifyLogin();

		$page = new PageAdmin();

		$page->setTpl('categories-create');

	});

	$app->post('/admin/categories/create', function(){

		User::verifyLogin();

		$category = new Category();

		$category->setValues($_POST);

		$category->save();

		header('Location: /admin/categories');
		exit;

	});

	$app->get('/admin/categories/:idcategory/delete', function($idcategory){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$category->delete();

		header('location: /admin/categories');
		exit;
	});
	
	$app->get('/admin/categories/:idcategory', function($idcategory){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$page->setTpl('categories-update', [
			'category'=>$category->getValues()
		]);

	});

	$app->post('/admin/categories/:idcategory', function($idcategory){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$category->setValues($_POST);

		$category->save();
		
		header('location: /admin/categories');
		exit;
	});

	$app->get('/admin/categories/:idcategory/products', function($idcategory){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$page->setTpl('categories-products', [
			'category'=>$category->getValues(),
			'productsRelated'=>$category->getProducts(),
			'productsNotRelated'=>$category->getProducts(false)
		]);

	});

	$app->get('/admin/categories/:idcategory/products/:idproduct/add', function($idcategory, $idproduct){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$product = new Product();

		$product->get((int)$idproduct);

		$category->addProduct($product);

		header('location: /admin/categories/'.$idcategory.'/products');
		exit;

	});

$app->get('/admin/categories/:idcategory/products/:idproduct/remove', function($idcategory, $idproduct){

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$product = new Product();

		$product->get((int)$idproduct);

		$category->removeProduct($product);

		header('location: /admin/categories/'.$idcategory.'/products');
		exit;

	});


?>
<?php 

	use \Hcode\PageAdmin;
	use \Hcode\Model\User;
	
	
	// Rotas (CRUD)
	$app->get('/admin/users', function() {

		User::verifyLogin();

		$search = (isset($_GET['search'])) ? $_GET['search'] : '';

		$page = (isset($_GET['page'])) ? $_GET['page'] : 1;

		if ($search != '') {
			$pagination = User::getPageSearch($search, $page);
		} else {
			$pagination = User::getUsersPage($page);
		}

		$pages = [];

		for ($i = 1; $i <= $pagination['pages']; $i++) {

			array_push($pages, [
				'href'=>'/admin/users?'.http_build_query([
					'page'=>$i,
					'search'=>$search
				]),
				'text'=>$i
			]);
			
		}

		$page = new PageAdmin();

		$page->setTpl('users', array(
			'users'=>$pagination['data'],
			'search'=>$search,
			'pages'=>$pages
		));

	});

	$app->get('/admin/users/create', function() {

		User::verifyLogin();

		$page = new PageAdmin();

		$errorReg = User::getErrorRegister();

		if (isset($_SESSION['registerValues']) &&  $errorReg == '') {
			$_SESSION['registerValues'] = NULL;
		}

		$page->setTpl('users-create', [
			'error'=>$errorReg,
			'registerValues'=>isset($_SESSION['registerValues']) ? $_SESSION['registerValues'] : [
				'deslogin'=>'',
				'desperson'=>'',
				'desemail'=>'',
				'nrphone'=>''
			]
		]);
	});

	$app->post('/admin/users/create', function() {

		User::verifyLogin();

		$_SESSION['registerValues'] = $_POST;

		if (User::checkLoginExist($_POST['deslogin']) === true) {
			
			User::setErrorRegister('Este login já está sendo usado por outro usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if(isset($_POST['nrphone']) && !$_POST['nrphone'] == '' && !is_numeric($_POST['nrphone'])) { 

			User::setErrorRegister('Preencha o telefone apenas com numeros');
			header('Location: /admin/users/create');
			exit;

		} else if (User::checkEmailExist($_POST['desemail']) === true) {
			
			User::setErrorRegister('Este endereço de e-mail já está sendo usado por outro usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if($_POST['despassword_c'] != $_POST['despassword']) { 

			User::setErrorRegister('Confirmação de senha não confere, tente novamente');
			header('Location: /admin/users/create');
			exit;

		} else {

			$user = new User();

			$_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;

			$user->setData($_POST);

			$user->save();

			header('Location: /admin/users');
			exit;
		}
	});

	$app->get('/admin/users/:iduser/delete', function($iduser) {

		User::verifyLogin();

		$user = new User();

		$user->get((int)$iduser);

		$user->delete();

		header('Location: /admin/users');
		exit;

	});

	$app->get('/admin/users/:iduser', function($iduser) {

		User::verifyLogin();

		$user = new User();

    $user->get((int)$iduser);

		$page = new PageAdmin();

		$page->setTpl("users-update", array(
        "user"=>$user->getValues()
    ));
	});

	
	$app->post('/admin/users/:iduser', function($iduser) {

		User::verifyLogin();

		$user = new User();

		$_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;

		$user->get((int)$iduser);

		$user->setData($_POST);

		$user->update();

		header('Location: /admin/users');
		exit;
	});

?>
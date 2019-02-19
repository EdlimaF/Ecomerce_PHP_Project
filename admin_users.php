<?php 

	use \Application\PageAdmin;
	use \Application\Model\User;
	
	
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

		if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
			
			User::setErrorRegister('Informe o nome do usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if (!isset($_POST['deslogin']) || $_POST['deslogin'] === '') {
			
			User::setErrorRegister('Informe o login do usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if (User::checkLoginExist($_POST['deslogin']) === true) {
			
			User::setErrorRegister('Este login já está sendo usado por outro usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if(isset($_POST['nrphone']) && !$_POST['nrphone'] == '' && !is_numeric($_POST['nrphone'])) { 

			User::setErrorRegister('Preencha o telefone apenas com numeros.');
			header('Location: /admin/users/create');
			exit;

		} else if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
			
			User::setErrorRegister('Informe o email do usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if (User::checkEmailExist($_POST['desemail']) === true) {
			
			User::setErrorRegister('Este endereço de e-mail já está sendo usado por outro usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if (!isset($_POST['despassword']) || $_POST['despassword'] === '') {
			
			User::setErrorRegister('Informe a senha do usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if (!isset($_POST['despassword_c']) || $_POST['despassword_c'] === '') {
			
			User::setErrorRegister('Confirme a senha do usuário.');
			header('Location: /admin/users/create');
			exit;

		} else if($_POST['despassword_c'] != $_POST['despassword']) { 

			User::setErrorRegister('Confirmação de senha não confere, tente novamente');
			header('Location: /admin/users/create');
			exit;

		} else {

			$user = new User();

			$_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;

			$user->setValues($_POST);

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

		$currentid = (int)User::getFromSession()->getiduser();

		// Atualiza usuario da seção
		if ($currentid == $iduser) {
			User::logout();
			User::verifyLogin();
		}

		header('Location: /admin/users');
		exit;

	});

	$app->get('/admin/users/:iduser', function($iduser) {

		User::verifyLogin();

		$user = new User();

    $user->get((int)$iduser);

		$page = new PageAdmin();

		$page->setTpl("users-update", array(
        "user"=>$user->getValues(),
        'msgError'=>User::getError(),
        'msgSuccess'=>User::getSuccess()
    ));
	});

	
	$app->post('/admin/users/:iduser', function($iduser) {

		User::verifyLogin();

		$user = new User();

		$user->get((int)$iduser);

		if (!isset($_POST['desperson']) || $_POST['desperson'] == '') {

			User::setError('Informe o nome do usuário.');
			header('Location: /admin/users/'.$iduser);
			exit;

		} else if (!isset($_POST['deslogin']) || $_POST['deslogin'] == '') {

			User::setError('Informe o login do usuário.');
			header('Location: /admin/users/'.$iduser);
			exit;
			
		} else if (User::checkLoginExist($_POST['deslogin']) === true && $_POST['deslogin'] != $user->getdeslogin()) {

			User::setError('Este login já está sendo usado por outro usuário.');
			header('Location: /admin/users/'.$iduser);
			exit;

		} else if (!isset($_POST['desemail']) || $_POST['desemail'] == '') {

			User::setError('Informe o email do usuário.');
			header('Location: /admin/users/'.$iduser);
			exit;
			
		} else if (User::checkEmailExist($_POST['desemail']) === true && $_POST['desemail'] != $user->getdesemail()) {

			User::setError('Este endereço de e-mail já está sendo usado por outro usuário.');
			header('Location: /admin/users/'.$iduser);
			exit;
			
		} else {

			if (isset($_POST['despassword']) && $_POST['despassword'] != '') {

				if (!isset($_POST['despassword_c']) || $_POST['despassword_c'] == '') {

					User::setError('Confirme a nova senha.');
					header('Location: /admin/users/'.$iduser);
					exit;

				} else if ($_POST['despassword_c'] != $_POST['despassword']) {

					User::setError('Erro na confirmação da senha.');
					header('Location: /admin/users/'.$iduser);
					exit;

				} else {


					$_POST['despassword'] = User::getPasswordHash($_POST['despassword']);

				}

			} else {
				unset($_POST['despassword']);
			}

			$_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;
		
			$user->setValues($_POST);

			$user->update();

			$currentIduser = User::getFromSession()->getiduser();

			// Atualiza usuario da seção
			if ($currentIduser == $iduser) {

			 	$_SESSION[User::SESSION] = $user->getValues();

			 	//Se não for administrador não tem mensage, vai voltar pro login
				if ($_POST['inadmin'] == 1) {
					User::setSuccess('Dados salvos com sucesso.');
				}

			} else {
				User::setSuccess('Dados salvos com sucesso.');
			}


			header('Location: /admin/users/'.$iduser);
			exit;
		} 
			
	});

	$app->get('/admin/users/:iduser/password', function($iduser){

		User::verifyLogin();

		$page = new PageAdmin();

		$page->setTpl('users-password', [
			'iduser'=>$iduser,
			'msgError'=>User::getError(),
			'msgSuccess'=>User::getSuccess()
		]);

	});

	$app->post('/admin/users/:iduser/password', function($iduser){

		User::verifyLogin();

		// validação de campos
		if (!isset($_POST['despassword']) || $_POST['despassword'] == '') {

			User::setError('Informe a nova senha!');

			header('Location: /admin/users/'.$iduser.'/password');
			exit;
			
		} else if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] == '') {

			User::setError('Confirme a nova senha!');

			header('Location: /admin/users/'.$iduser.'/password');
			exit;
			
		} else if ($_POST['despassword'] != $_POST['despassword-confirm']) {

			User::setError('Confirmação da nova senha invalida!');

			header('Location: /admin/users/'.$iduser.'/password');
			exit;
			
		} else {

			$user = new User();

			$user->get((int)$iduser);

			$user->setPassword($_POST['despassword']);

			$currentLogin = User::getFromSession()->getdeslogin();

			// Atualiza usuario da seção
			if ($currentLogin == $user->getdeslogin()) {
				$_SESSION[User::SESSION] = $user->getValues();
			}
			
			User::setSuccess('Senha modificada com sucesso.');

			header('Location: /admin/users/'.$iduser.'/password');
			exit;
		}

	});



?>
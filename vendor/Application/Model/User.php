<?php

	namespace Application\Model;

	use \Application\Db\Sql;
	use \Application\Model;
	use \Application\Mailer;

	class User extends  Model 
	{

		const SESSION = 'User';
		const ERROR = 'UserError';
		const ERROR_REGISTER = 'UserErrorRegister';
		const SUCCESS = 'UserSucess';

		// Criptografia
		const SECRET_KEY = 'erika@cristina02'; // 16 caracteres no minimo
		const SECRET_IV = 'elf@221071'; // 16 caracteres no minimo

		public static function getFromSession()
		{

			$user = new User();

			if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
				
				$user->setValues($_SESSION[User::SESSION]);
			}

			return $user;
		}

		public static function checkLogin($inadmin = true)
		{

			if (
				!isset($_SESSION[User::SESSION])
				||
				!$_SESSION[User::SESSION]
				||
				!(int)$_SESSION[User::SESSION]['iduser'] > 0
			) {

				//Não ta logado
				return false;
			} else {

				if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
					
					return true;
				} else if ($inadmin === false){

					return true;
				} else {

					return false;					
				}
			}		
		}

		public static function login($login, $password)
		{

			$sql = new Sql();

			$results = $sql->select('SELECT * FROM tb_users a 
									INNER JOIN tb_persons b 
									ON a.iduser = b.iduser 
									WHERE a.deslogin = :LOGIN', [
				':LOGIN'=>$login
			]);

			if (count($results) === 0) {
				throw new \Exception("Usuário inexistente ou senha invalida");
			}

			$data = $results[0];
			
			if (password_verify($password, $data['despassword']) === true) {

				$user = new User();

				$user->setValues($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;
			} else {

				throw new \Exception("Usuário inexistente ou senha invalida");
			}
		}// Fim function login

		public static function verifyLogin($inadmin = true, $checkout = false)
		{

			if (!User::checkLogin($inadmin)) {

				if ($inadmin) {

					header('Location: /admin/login');
					
				} else {

					if ($checkout) $_SESSION['checkout'] = true;

					header('Location: /login');

				}
				exit;
			}

		}// Fim function verifyLogin

		public static function logout()
		{

			$_SESSION[User::SESSION] = NULL;
		}

		public static function listAll()
		{

			$sql = new Sql();

			return $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(iduser) ORDER BY b.desperson');
		}

		// Com stored procedure
		/*public function save()
		{

			$sql = new Sql();

			$results = $sql->select('CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)', array(
				':desperson'=>$this->getdesperson(),
				':deslogin'=>$this->getdeslogin(),
				':despassword'=>User::getPasswordHash($this->getdespassword()),
				':desemail'=>$this->getdesemail(),
				':nrphone'=>(int)$this->getnrphone(),
				':inadmin'=>$this->getinadmin()
			));

			$this->setValues($results[0]);

			//return isset($results[0]);
		}*/

		// Sem Stored Procedure
		public function save()
		{

			$sql = new Sql();

			$results = $sql->query('INSERT INTO tb_users (deslogin, despassword, inadmin)
    		VALUES(:deslogin, :despassword, :inadmin)', array(
				':deslogin'=>$this->getdeslogin(),
				':despassword'=>User::getPasswordHash($this->getdespassword()),
				':inadmin'=>$this->getinadmin()
			));

			$results = $sql->select('SELECT LAST_INSERT_ID() as lastid FROM tb_users LIMIT 1');

			$iduser = $results[0]['lastid'];

			$sql->query('INSERT INTO tb_persons (iduser, desperson, desemail, nrphone) VALUES (:iduser, :desperson, :desemail, :nrphone)', array(
				':iduser'=>$iduser,
				':desperson'=>$this->getdesperson(),
				':desemail'=>$this->getdesemail(),
				':nrphone'=>$this->getnrphone()
			));

			$results = $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(iduser) WHERE a.iduser = LAST_INSERT_ID()');

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public function get($iduser) 
		{

			$sql = new Sql();
                              
			$results = $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(iduser) WHERE a.iduser = :iduser', array(
				':iduser'=>$iduser
			));

			if (count($results[0]) > 0) {
				$this->setValues($results[0]);
			}
			
		}

		// Paginação dos itens no site
		public static function getUsersPage($page = 1, $itemsPerPage = 10)
		{

			$start =($page - 1) * $itemsPerPage;

			$sql = new Sql();

			$results = $sql->select("
				SELECT SQL_CALC_FOUND_ROWS *
				FROM tb_users a
				INNER JOIN tb_persons b ON a.iduser = b.iduser
				ORDER BY b.desperson
				LIMIT $start, $itemsPerPage;
			");

			$resultTotal = $sql->select('SELECT FOUND_ROWS() AS nrtotal;');

			return [
				'data'=>$results,
				'total'=>(int)$resultTotal[0]['nrtotal'],
				'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
			];
		} // Fim paginação dos itens no site

	
		// Paginação dos itens com busca
		public static function getPagesearch($search, $page = 1, $itemsPerPage = 10)
		{

			$start =($page - 1) * $itemsPerPage;

			$sql = new Sql();

			$results = $sql->select("
					SELECT SQL_CALC_FOUND_ROWS *
					FROM tb_users a
					INNER JOIN tb_persons b ON a.iduser = b.iduser
					WHERE b.desperson Like :search OR b.desemail = :search OR a.deslogin LIKE :search
					ORDER BY b.desperson
					LIMIT $start, $itemsPerPage;
				", [
					':search'=>'%'.$search.'%'
				]);

			$resultTotal = $sql->select('SELECT FOUND_ROWS() AS nrtotal;');

			return [
				'data'=>$results,
				'total'=>(int)$resultTotal[0]['nrtotal'],
				'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
			];
		} // Fim Paginação dos itens com busca

		
		/*public function update()
		{

			$sql = new Sql();
		
			$results = $sql->select('CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)', array(
				':iduser'     =>$this->getiduser(),
				':desperson'  =>$this->getdesperson(),
				':deslogin'   =>$this->getdeslogin(),
				':despassword'=>$this->getdespassword(),
				':desemail'   =>$this->getdesemail(),
				':nrphone'    =>$this->getnrphone(),
				':inadmin'    =>$this->getinadmin()

			));

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}

		}*/

		// sem stored procedure
		public function update()
		{

			$iduser = $this->getiduser();

			$sql = new Sql();
		
			$sql->query('
							UPDATE tb_users
    					SET	deslogin = :deslogin,	despassword = :despassword,	inadmin = :inadmin
							WHERE iduser = :iduser;', array(
				':iduser'     =>$iduser,
				':deslogin'   =>$this->getdeslogin(),
				':despassword'=>$this->getdespassword(),
				':inadmin'    =>$this->getinadmin()

			));

			$sql->query('
							UPDATE tb_persons
    					SET desperson = :desperson, desemail = :desemail, nrphone = :nrphone
							WHERE iduser = :iduser;', array(
				':iduser'     =>$iduser,
				':desperson'  =>$this->getdesperson(),
				':desemail'   =>$this->getdesemail(),
				':nrphone'    =>$this->getnrphone()

			));

			$results = $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(iduser) WHERE a.iduser = :iduser', [
				':iduser'=>$iduser
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}

		}

		/*public function delete()
		{

			$sql = new Sql();

			$sql->query('CALL sp_users_delete(:iduser)', array(
				':iduser'=>$this->getiduser()
			));
		}*/

		// sem stored procedure
		public function delete()
		{

			$sql = new Sql();

			$results = $sql->query('
					DELETE FROM tb_orders WHERE iduser = :iduser;
					DELETE FROM tb_carts WHERE iduser = :iduser;
					DELETE FROM tb_users WHERE iduser = :iduser;
				', array(
				':iduser'=>$this->getiduser()
			));

		}

		public static function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {
			
			$output = false;

			$encrypt_method = "AES-256-CBC";
			
		  // hash
			$key = hash('sha256', $secret_key);

		  // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
			$iv = substr(hash('sha256', $secret_iv), 0, 16);

			if ( $action == 'encrypt' ) {
				$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
				$output = base64_encode($output);
			} else if( $action == 'decrypt' ) {
				$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
			}

			return $output;
		}

		public static function getForgot($email, $inadmin = true)
		{
			
			$sql = new Sql();

			$results = $sql->select('
				SELECT *
				FROM tb_persons a
				INNER JOIN tb_users b USING(iduser)
				WHERE a.desemail = :email;
				', array(

				':email'=>$email
			));

			if (count($results) === 0) 
			{
				throw new \Exception('Não foi possivel recuperar a senha.');
			}
			else
			{
				$data = $results[0];
				
				/*$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER['REMOTE_ADDR']
				));*/
			
				// sem stored procedure
				$sql->query("
							INSERT INTO tb_userspasswordsrecoveries (iduser, desip)
    					VALUES(:iduser, :desip);", array(
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER['REMOTE_ADDR']
				));

				$results2 = $sql->select('SELECT * FROM tb_userspasswordsrecoveries
    					WHERE idrecovery = LAST_INSERT_ID()');
				////////////////////////////

				if (count($results2) === 0) 
				{
					throw new \Exception('Não foi possivel recuperar a senha.');	
				}
				else
				{

					$dataRecovery = $results2[0];

					// Código anterior que foi depreciado a partir da versão 7.1 do php
					/*$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery['idrecovery'], MCRYPT_MODE_ECB));*/

					// Criptografia do codigo
					
    			$code = User::encrypt_decrypt('encrypt', $dataRecovery['idrecovery'], User::SECRET_KEY, User::SECRET_IV);
	    		
	    		if ($inadmin) {

	    			$link = "http://www.virtualcompras.com.br/admin/forgot/reset?code=$code";
	    					
	    		}	else {

	    			$link = "hhttp://www.virtualcompras.com.br/forgot/reset?code=$code";

	    		}	

					$mailer = new Mailer($data['desemail'], $data['desperson'], 'Redefinir senha da EL Store.', 'forgot', array(
						'name'=>$data['desperson'],
						'link'=>$link
					));

					$mailer->send();

					return $data;
				}

			}

		}

		public static function validForgotDecrypt($code) 
		{

	    	$idrecovery = User::encrypt_decrypt('decrypt', $code, User::SECRET_KEY, User::SECRET_IV);
			
			$sql = new sql();

			$results = $sql->select("
				SELECT *
				FROM tb_userspasswordsrecoveries a
				INNER JOIN tb_users b USING(iduser)
				INNER JOIN tb_persons c USING(iduser)
				WHERE
					a.idrecovery = :idrecovery
					AND
					a.dtrecovery IS NULL
					AND
					DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();

			", array(
				':idrecovery'=>$idrecovery
			));

			if (count($results) === 0) {
				throw new \Exception('Não foi possivel recuperar a senha.');
			}
			else
			{
				return $results[0];
			}

		}

		public static function setForgotUsed($idrecovery)
		{
			$sql = new Sql();

			$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
				':idrecovery'=>$idrecovery
			));
		}

		public function setPassword($password)
		{

			$sql = new Sql();

			$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
				':password'=>User::getPasswordHash($password),
				':iduser'=>$this->getiduser()

			));
		}

		public static function getPasswordHash($password)
		{

			return password_hash($password, PASSWORD_DEFAULT, ['cost'=>12]);

		}

		public static function checkLoginExist($login)
		{

			$sql = new SQL();

			$results = $sql->select('SELECT * FROM tb_users WHERE deslogin = :deslogin', [
				':deslogin'=>$login
			]);

			return (count($results) > 0);
		}

		public static function checkEmailExist($desemail)
		{

			$sql = new SQL();

			$results = $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(iduser) 
				WHERE b.desemail = :desemail', [
				':desemail'=>$desemail
			]);

			return (count($results) > 0);
		}

		public function getOrders()
	  {

			$sql = new Sql();

	  	$results = $sql->select('
	  		SELECT * 
	  		FROM tb_orders a 
	  		INNER JOIN tb_ordersstatus b USING(idstatus)
	  		INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idorder)
			INNER join tb_persons f ON f.iduser = d.iduser
			WHERE a.iduser = :iduser
	  	', [
	  		':iduser'=>$this->getiduser()
	  	]);	

	  	return $results;  	
	  	
	  }

		public static function setError($msg)
		{

			$_SESSION[User::ERROR] = $msg;

		}

		public static function getError()
		{

			$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

			User::clearError();
			
			return $msg;

		}

		public static function clearError()
		{

			$_SESSION[User::ERROR] = NULL;

		}

		public static function setSuccess($msg)
		{

			$_SESSION[User::SUCCESS] = $msg;

		}

		public static function getSuccess()
		{

			$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

			User::clearSuccess();
			
			return $msg;

		}

		public static function clearSuccess()
		{

			$_SESSION[User::SUCCESS] = NULL;

		}

		public static function setErrorRegister($msg)
		{

			$_SESSION[User::ERROR_REGISTER] = $msg;

		}

		public static function getErrorRegister()
		{

			$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

			User::clearErrorRegister();

			return $msg;

		}

		public static function clearErrorRegister()
		{

			$_SESSION[User::ERROR_REGISTER] = NULL;

		}


		
		
	}

?>
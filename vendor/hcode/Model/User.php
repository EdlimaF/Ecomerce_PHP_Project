<?php

	namespace Hcode\Model;

	use \Hcode\Db\Sql;
	use \Hcode\Model;
	use \Hcode\Mailer;

	class User extends  Model 
	{

		const SESSION = 'User';

		// Criptografia
		const SECRET_KEY = 'erika@cristina02'; // 16 caracteres no minimo
		const SECRET_IV = 'elf@221071'; // 16 caracteres no minimo

		public static function getFromSession()
		{

			$user = new User();

			if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
				
				$user->setData($_SESSION[User::SESSION]);
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

			}	else {

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

			$results = $sql->select('SELECT * FROM tb_users WHERE deslogin = :LOGIN', array(
				':LOGIN'=>$login
			));

			if (count($results) === 0)
			{
				throw new \Exception("Usuário inexistente ou senha invalida");
			}

			$data = $results[0];
			
			if (password_verify($password, $data['despassword']) === true)
			{

				$user = new User();

				$user->setData($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;

			} 
			else 
			{

				throw new \Exception("Usuário inexistente ou senha invalida");

			}
		}// Fim function login

		public static function verifyLogin($inadmin = true)
		{


			if (!User::checkLogin($inadmin)) {

				header('Location: /admin/login');
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

			return $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson');
		}

		public function save()
		{

			$sql = new Sql();
			$results = $sql->select('CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)', array(
				':desperson'=>$this->getdesperson(),
				':deslogin'=>$this->getdeslogin(),
				':despassword'=>$this->getdespassword(),
				':desemail'=>$this->getdesemail(),
				':nrphone'=>$this->getnrphone(),
				':inadmin'=>$this->getinadmin()
			));

			$this->setData($results[0]);

		}

		public function get($iduser) 
		{

			$sql = new Sql();
                              
			$results = $sql->select('SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser', array(
				':iduser'=>$iduser
			));

			$data = $results[0];

			$data['desperson'] = utf8_encode($data['desperson']);

			$this->setData($results[0]);
		}

		
		public function update()
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

			$this->setData($results[0]);

		}

		public function delete()
		{

			$sql = new Sql();

			$sql->query('CALL sp_users_delete(:iduser)', array(
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

		public static function getForgot($email)
		{
			
			$sql = new Sql();

			$results = $sql->select('
				SELECT *
				FROM tb_persons a
				INNER JOIN tb_users b USING(idperson)
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
				
				$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER['REMOTE_ADDR']
				));
			

				if (count($results2) === 0) 
				{
					throw new \Exception('Não foi possivel recuperar a senha.');	
				}
				else
				{

					$dataRecovery = $results2[0];

					// echo $dataRecovery['idrecovery'] .'<br>';

					// Código anterior que foi depreciado a partir da versão 7.1 do php
					/*$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery['idrecovery'], MCRYPT_MODE_ECB));*/

					// Criptografia do codigo
					
	    			$code = User::encrypt_decrypt('encrypt', $dataRecovery['idrecovery'], User::SECRET_KEY, User::SECRET_IV);
	    				

					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

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
				INNER JOIN tb_persons c USING(idperson)
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
				':password'=>$password,
				':iduser'=>$this->getiduser()

			));
		}
		

	}

?>
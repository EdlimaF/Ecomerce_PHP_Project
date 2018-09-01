<?php 

	namespace Hcode\Model;

	use \Hcode\DB\Sql;
	use \Hcode\Model;
	use \Hcode\Model\Address;
	

	class Order extends Model
	{

		const ERROR = 'OrderError';
		const SUCCESS = 'OrderSucess';

	  public function save()
	  {

	  	$sql = new Sql();

	  	$results = $sql->select('CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vlfreight, :vlsubtotal, :vltotal)', [
	  		':idorder'=>$this->getidorder(),
	  		':idcart'=>$this->getidcart(),
	  		':iduser'=>$this->getiduser(),
	  		':idstatus'=>$this->getidstatus(),
	  		':idaddress'=>$this->getidaddress(),
	  		':vlfreight'=>$this->getvlfreight(),
	  		':vlsubtotal'=>$this->getvlsubtotal(),
	  		':vltotal'=>$this->getvltotal()

	  	]);

	  	if (count($results) > 0) {
	  		$this->setData($results[0]);
	  		return true;  		
	  	} else {
	  		return false;
	  	}
	      
	  }

	  public function get($idorder)
	  {

	  	$sql = new Sql();

	  	$results = $sql->select('
	  		SELECT * 
	  		FROM tb_orders a 
	  		INNER JOIN tb_ordersstatus b USING(idstatus)
	  		INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER join tb_persons f ON f.idperson = d.idperson
				WHERE a.idorder = :idorder
	  	', [
	  		':idorder'=>$idorder
	  	]);

	  	if (count($results) > 0) {
	  		$this->setData($results[0]);
	  	}

	  }

	  public function getProducts($idorder)
	  {

	  	$sql = new Sql();

			$rows = $sql->select('SELECT * FROM  tb_ordersproducts WHERE idorder = :idorder', [
				':idorder'=>$idorder
			]);

			return $rows;
	  }

	  public function addProduct($cartProducts)
		{

			$sql = new Sql();

			$sql->query('INSERT INTO tb_ordersproducts (idorder, idproduct, desproduct, vlprice, nrqtd, vltotal) 
									 VALUES (:idorder, :idproduct, :desproduct, :vlprice, :nrqtd, :vltotal)', [
				':idorder'=>$this->getidorder(),
				':idproduct'=>$cartProducts['idproduct'],
				':desproduct'=>$cartProducts['desproduct'],
				':vlprice'=>$cartProducts['vlprice'],
				':nrqtd'=>$cartProducts['nrqtd'],
				':vltotal'=>$cartProducts['vltotal']
			]);
		}

		public static function listAll()
		{

			$sql = new Sql();

			return $sql->select('
	  		SELECT * 
	  		FROM tb_orders a 
	  		INNER JOIN tb_ordersstatus b USING(idstatus)
	  		INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER join tb_persons f ON f.idperson = d.idperson
				ORDER BY a.dtregister DESC
	  	');

		}

		public function delete()
		{

			$sql = new Sql();

			$sql->query('DELETE FROM tb_orders WHERE idorder = :idorder', [
				':idorder'=>$this->getidorder()
			]);

			$address = new Address();

			$address->get((int)$this->getidaddress());

			$address->delete();

		}

		public static function setError($msg)
		{

			$_SESSION[Order::ERROR] = $msg;

		}

		public static function getError()
		{

			$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

			Order::clearError();
			
			return $msg;

		}

		public static function clearError()
		{

			$_SESSION[Order::ERROR] = NULL;

		}

		public static function setSuccess($msg)
		{

			$_SESSION[Order::SUCCESS] = $msg;

		}

		public static function getSuccess()
		{

			$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

			Order::clearSuccess();
			
			return $msg;

		}

		public static function clearSuccess()
		{

			$_SESSION[Order::SUCCESS] = NULL;

		}

	}

?>
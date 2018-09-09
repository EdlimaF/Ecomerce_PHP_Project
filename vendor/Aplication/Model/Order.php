<?php 

	namespace Aplication\Model;

	use \Aplication\DB\Sql;
	use \Aplication\Model;
	use \Aplication\Model\Address;
	

	class Order extends Model
	{

		const ERROR = 'OrderError';
		const SUCCESS = 'OrderSucess';

	  public function save()
	  {

	  	$sql = new Sql();

	  	$results = $sql->select('CALL sp_orders_save(:idorder, :iduser, :idstatus, :vlfreight, :vlsubtotal, :vltotal)', [
	  		':idorder'=>$this->getidorder(),
	  		':iduser'=>$this->getiduser(),
	  		':idstatus'=>$this->getidstatus(),
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
	  		SELECT *,
	  		a.dtregister 
	  		FROM tb_orders a 
	  		INNER JOIN tb_ordersstatus b USING(idstatus)
	  		INNER JOIN tb_users d USING(iduser)
				INNER JOIN tb_addresses e USING(idorder)
				INNER join tb_persons f USING(iduser)
				WHERE a.idorder = :idorder
	  	', [
	  		':idorder'=>$idorder
	  	]);

	  	if (count($results) > 0) {
	  		$this->setData($results[0]);
	  	}

	  }

	  // Paginação dos itens no site
		public static function getOrdersPage($page = 1, $itemsPerPage = 10)
		{

			$start =($page - 1) * $itemsPerPage;

			$sql = new Sql();

			$results = $sql->select("
					SELECT SQL_CALC_FOUND_ROWS 
					*,
					a.dtregister
					FROM tb_orders a
					INNER JOIN tb_ordersstatus b USING(idstatus)
					INNER JOIN tb_users c USING(iduser)
					INNER JOIN tb_persons d USING(iduser)
					ORDER BY a.dtregister DESC
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
					SELECT SQL_CALC_FOUND_ROWS *,
					a.dtregister
					FROM tb_orders a
					INNER JOIN tb_ordersstatus b USING(idstatus)
					INNER JOIN tb_users c USING(iduser)
					INNER JOIN tb_persons d USING(iduser)
					WHERE a.idorder = :id OR d.desperson LIKE :search OR b.desstatus LIKE :search
					ORDER BY a.dtregister DESC
					LIMIT $start, $itemsPerPage;
				", [
					':search'=>'%'.$search.'%',
					':id'=>$search
				]);

			$resultTotal = $sql->select('SELECT FOUND_ROWS() AS nrtotal;');

			return [
				'data'=>$results,
				'total'=>(int)$resultTotal[0]['nrtotal'],
				'pages'=>ceil($resultTotal[0]['nrtotal'] / $itemsPerPage)
			];
		} // Fim Paginação dos itens com busca

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
	  		SELECT *,
	  		a.dtregister 
	  		FROM tb_orders a 
	  		INNER JOIN tb_ordersstatus b USING(idstatus)
	  		INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idorder)
				INNER join tb_persons f ON f.iduser = d.iduser
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
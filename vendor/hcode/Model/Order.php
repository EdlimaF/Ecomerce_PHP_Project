<?php 

	namespace Hcode\Model;

	use \Hcode\DB\Sql;
	use \Hcode\Model;
	

	class Order extends Model
	{

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
	  		-- INNER JOIN tb_carts c USING(idcart)
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
	  
	}

?>
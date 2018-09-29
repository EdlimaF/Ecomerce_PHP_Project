<?php

	namespace Application\Model;

	use \Application\Db\Sql;
	use \Application\Model;
	

	class Product extends  Model 
	{

		public static function listAll($order = 'desproduct', $limit = '')
		{

			$sql = new Sql();

			return $sql->select('SELECT * FROM tb_products	ORDER BY '.$order.' '.$limit);
		}

		public static function CheckList($list)
		{

			foreach ($list as &$row) {

				$p = new Product();
				$p->setValues($row);
				$row = $p->getValues();
			}

			return $list;
		}

		
		public function save()
		{

			$sql = new Sql();


			$results = $sql->select('CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)', array(
				':idproduct'=>$this->getidproduct(),
				':desproduct'=>$this->getdesproduct(),
				':vlprice'=>$this->getvlprice(),
				':vlwidth'=>$this->getvlwidth(),
				':vlheight'=>$this->getvlheight(),
				':vllength'=>$this->getvllength(),
				':vlweight'=>$this->getvlweight(),
				':desurl'=>$this->getdesurl()
			));

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public function get($idproduct)
		{

			$sql = new Sql();

			$results = $sql->select('SELECT * FROM tb_products WHERE idproduct = :idproduct', [
				':idproduct'=>$idproduct
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}

		}

		// Paginação dos itens no site
		public static function getProductsPage($page = 1, $itemsPerPage = 10)
		{

			$start =($page - 1) * $itemsPerPage;

			$sql = new Sql();

			$results = $sql->select("
					SELECT SQL_CALC_FOUND_ROWS *
					FROM tb_products
					ORDER BY desproduct
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
					FROM tb_products
					WHERE desproduct Like :search
					ORDER BY desproduct
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


		public function delete()
		{

			$sql = new Sql();

			$sql->query('DELETE FROM tb_products WHERE idproduct = :idproduct', [
				':idproduct'=>$this->getidproduct()
			]);

		}

		public function checkPhoto()
		{

			$idproduct = $this->getidproduct();

			if (file_exists(
				$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
				'res' . DIRECTORY_SEPARATOR .
				'site' . DIRECTORY_SEPARATOR .
				'img' . DIRECTORY_SEPARATOR  .
				'products' . DIRECTORY_SEPARATOR .
				$idproduct . '.jpg'
				)) {

				$url = '/res/site/img/products/' . $idproduct . '.jpg';

			} else {

				$url =  '/res/site/img/product.jpg';
			}

			return $this->setdesphoto($url);
		}

		public function getValues() 
		{

			$this->checkPhoto();

			$values = parent::getValues();

			return $values;
		}

		public function setPhoto($file)
		{

			$idproduct = $this->getidproduct();

			if ($file['name'] != '') {

				$extension = explode('.', $file['name']);
				$extension = end($extension);

				switch ($extension) {
					case 'jpg':
					case 'jpeg':
						$image = imagecreatefromjpeg($file['tmp_name']);
						break;

					case 'gif':
						$image = imagecreatefromgif($file['tmp_name']);
						break;

					case 'png':
						$image = imagecreatefrompng($file['tmp_name']);
						break;
				}

				$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
					'res' . DIRECTORY_SEPARATOR .
					'site' . DIRECTORY_SEPARATOR .
					'img' . DIRECTORY_SEPARATOR  .
					'products' . DIRECTORY_SEPARATOR .
					$idproduct . '.jpg';

				imagejpeg($image, $dist);

				imagedestroy($image);

			}
			
			$this->checkPhoto();
		}
		

		public function getFromURL($desurl)
		{

			$sql = new Sql();

			$row = $sql->select('SELECT * FROM tb_products	WHERE desurl = :desurl LIMIT 1', [
				':desurl'=>$desurl
			]);

			if (count($row) > 0) {
				$this->setValues($row[0]);
			}

		}

		public function getCategories()
		{

			$sql = new Sql();

			return $sql->select('
				SELECT * FROM tb_categories a 
				INNER JOIN tb_productscategories b
				ON a.idcategory = b.idcategory
				WHERE b.idproduct = :idproduct
				', [
					':idproduct'=>$this->getidproduct()
			]);

		}

		
	}

?>
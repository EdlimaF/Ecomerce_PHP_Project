<?php

	namespace Application\Model;

	use \Application\Db\Sql;
	use \Application\Model;

	class Cart extends  Model 
	{

		const SESSION = 'Cart';		
		const SESSION_ERROR = 'CartError';		

		public static function getFromSession() {

			$cart = new Cart();

			if (isset($_SESSION[Cart::SESSION]['idcart']) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {

				$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
			} else {

				$cart->getFromSessionID();
				
				if (!(int)$cart->getidcart() > 0) {

					$data = [
						'dessessionid'=>session_id()
					];

					if (User::checkLogin(false)) {

						$user = User::getFromSession();

						$data['iduser'] = $user->getiduser();
					}

					$cart->setValues($data);

					$cart->save();

					$cart->setToSession();
				} else {

					$data = [
						'dessessionid'=>session_id()
					];

					$cart->setValues($data);

					$cart->save();

					$cart->setToSession();
				}	
			}	

			return $cart;
		}

		public function setToSession() {

			$_SESSION[Cart::SESSION] = $this->getValues();
		}

		public function getFromSessionID() {

			$sql = new Sql();

			$results = $sql->select('SELECT * FROM tb_carts WHERE dessessionid = :dessessionid', [
				':dessessionid'=>session_id()
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public function get(int $idcart) {

			$sql = new Sql();

			$results = $sql->select('SELECT * FROM tb_carts WHERE idcart = :idcart', [
				':idcart'=>$idcart
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public function save() {

			$sql = new Sql();

			$results = $sql->select('CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)', array(
				':idcart'      =>$this->getidcart(),
				':dessessionid' =>$this->getdessessionid(),
				':iduser'      =>$this->getiduser(),
				':deszipcode'  =>$this->getdeszipcode(),
				':vlfreight'   =>$this->getvlfreight(),
				':nrdays'      =>$this->getnrdays(),
			));

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public function delete() {

			$sql = new Sql();

			$sql->query('DELETE FROM tb_carts WHERE idcart = :idcart', [
				':idcart'=>$this->getidcart()
			]);

		}

		public function addProduct(Product $product, $qtde = 1) {

			$sql = new Sql();


			for ($i = 0; $i < $qtde ; $i++) {

				$sql->query('INSERT INTO tb_cartsproducts (idcart, idproduct) 
										 VALUES (:idcart, :idproduct)', [
					':idcart'=>$this->getidcart(),
					':idproduct'=>$product->getidproduct()
				]);
			}

			$this->getCalculateTotal();
		}

		public function removeProduct(Product $product, $all = false) {

			$sql = new Sql();

			if ($all) {

				$sql->query('UPDATE tb_cartsproducts 
										 SET dtremoved = NOW() 
										 WHERE idcart = :idcart 
										 AND idproduct = :idproduct 
										 AND  dtremoved IS NULL' , [
					':idproduct'=>$product->getidproduct(),
					':idcart'=>$this->getidcart()
				]);
			} else {

				$sql->query('UPDATE tb_cartsproducts 
										 SET dtremoved = NOW() 
										 WHERE idcart = :idcart 
										 AND idproduct = :idproduct 
										 AND  dtremoved IS NULL 
										 LIMIT 1' , [ // limitação para excluir apenas um item
					':idproduct'=>$product->getidproduct(),
					':idcart'=>$this->getidcart()
				]);
			}

			$this->getCalculateTotal();
		}

		
		public function getProducts() {

			$sql = new Sql();

			$rows = $sql->select('
				SELECT b.idproduct, 
							 b.desproduct, 
							 b.vlprice, 
							 b.vlwidth, 
							 b.vlheight, 
							 b.vllength, 
							 b.vlweight, 
							 b.desurl, 
							 COUNT(*) AS nrqtd, 
							 SUM(b.vlprice) AS vltotal
				FROM tb_cartsproducts a
				INNER JOIN tb_products b ON a.idproduct = b.idproduct
				WHERE a.idcart = :idcart AND a.dtremoved IS NULL
				GROUP BY b.idproduct, 
								 b.desproduct, 
								 b.vlprice, 
								 b.vlwidth, 
								 b.vlheight, 
								 b.vllength, 
								 b.vlweight, 
								 b.desurl
				ORDER BY b.desproduct
			', [
				':idcart'=>$this->getidcart()
			]);

			return Product::checkList($rows);
		}

		public function getProductsTotals() {

			$sql = new Sql();

			$results = $sql->select('
				SELECT 
					SUM(vlprice) AS vlprice,
					SUM(vlwidth) AS vlwidth,
					SUM(vlheight) AS vlheight,
					SUM(vllength) AS vllength,
					SUM(vlweight) AS vlweight,
					COUNT(*) AS nrqtd
				FROM tb_products a 
				INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
				WHERE b.idcart = :idcart AND dtremoved IS NULL;


				', [
					':idcart'=>$this->getidcart()
			]);

			if (count($results) > 0) {
				return $results[0];	
			} else {
				return [];
			}
		}

		public function setFreight($nrzipcode) {

			$nrzipcode = str_replace('-', '', $nrzipcode);

			$totals = $this->getProductsTotals();


			if ((int)$totals['nrqtd'] > 0) {

				if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
				if ($totals['vllength'] < 16) $totals['vllength'] = 16;

				$qs = http_build_query([
					'nCdEmpresa' =>	'', 					// Nome da empresa
					'sDsSenha'   =>	'',						// Senha
					'nCdServico' =>	'40010', 				// Serviço
					'sCepOrigem' =>	'09853120',				// CEP de horigem
					'SCepDestino'=> $nrzipcode,				// CEP de destino
					'nVlPeso'=>$totals['vlweight'],			// Peso
					'nCdFormato'=>'1',						// Formato caixa
					'nVlComprimento'=>$totals['vllength'],	// Comprimento total
					'nVlAltura'=>$totals['vlheight'],		// Altura
					'nVlLargura'=>$totals['vlwidth'],		// Largura
					'nVlDiametro'=>'0',						// Diametro
					'sCdMaoPropria'=>'S',					// Serviço de Mão proria
					'nVlValorDeclarado'=>$totals['vlprice'],// valor declarado do pacote
					'sCdAvisoRecebimento'=>'S'				// Serviço de aviso de recebimento do pacote
				]);

				$xml = simplexml_load_file('http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?'.$qs);

				$result = $xml->Servicos->cServico;

				if ($result->MsgErro != '') {

					$msg = (string)$result->MsgErro;
					
					Cart::setMsgError($msg);

				} else {

					Cart::clearMsgError();

				}

				$this->setnrdays($result->PrazoEntrega);
				$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
				$this->setdeszipcode($nrzipcode);

				$this->save();

				return $result;
				
			} else {

				$this->setnrdays(0);
				$this->setvlfreight(0);
				$this->setdeszipcode('');

				$this->save();

				return [];
			}
		}

		public static function formatValueToDecimal($value):float {

			$value = str_replace('.', '', $value);
			return str_replace(',', '.', $value);
		}

		public static function setMsgError($msg) {

			$_SESSION[Cart::SESSION_ERROR] = $msg;
		}


		public static function getMsgError() {

			$msg = isset($_SESSION[Cart::SESSION_ERROR]) ? $_SESSION[Cart::SESSION_ERROR] : '';

			Cart::clearMsgError();
			
			return $msg;
		}

		public static function clearMsgError() {

			$_SESSION[Cart::SESSION_ERROR] = NULL;
		}

		public function updataFreight() {

			if ($this->getdeszipcode() != '') {

				$this->setFreight($this->getdeszipcode());
			}
		}

		public function getValues()	{

			$this->getCalculateTotal();

			return parent::getValues();
		}

		public function getCalculateTotal() {

			$this->updataFreight();

			$totals = $this->getProductsTotals();

			$this->setvlsubtotal($totals['vlprice']);
			$this->setvltotal($totals['vlprice'] + $this->getvlfreight());
		}
	}
?>
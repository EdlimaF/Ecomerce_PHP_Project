<?php

	namespace Hcode;

	class Model {

		private $values = [];

		// Detecta qual o metodo que foi chamado e o tipo se (Get ou Set) e tambem nome
		public function __call($name, $args)
		{

			$method = substr($name, 0, 3); // os tres primeiros caracteres do metodo chamado
			$fieldName = substr($name, 3, strlen($name)); // o restante dos caracteres metodo chamado

			var_dump($method, $fieldName);
			exit;
		}
	}

?>
<?php // Inicio do bloco php

	namespace Application;

	class Model {


		private $values = [];

		// Detecta qual o metodo que foi chamado e o tipo se (Get ou Set) e tambem nome
		public function __call($name, $args) {

			$method = substr($name, 0, 3); // os tres primeiros caracteres do metodo chamado
			$fieldName = substr($name, 3, strlen($name)); // o restante dos caracteres metodo chamado

			switch ($method) {
				case 'get':
					return (isset( $this->values[$fieldName])) ?  $this->values[$fieldName] : NULL;
				break;

				case 'set':
					$this->values[$fieldName] = $args[0];
				break;
			}// Fim Case
		}// Fim function __call

		public function setValues($data = array()) {

			foreach ($data as $key => $value) {

				$this->{'set' .$key}($value);
			}
		}// Fim funcion setValues

		public function getValues() {

			return $this->values;
		}
	}// Fim Class Model
?> <!-- fim Bloco PHP -->
<?php

trait CasterVal
{
	public static function ConvertValToType ( $val, $tipo )
	{
		if ($tipo === CasterVal_Constants::MIXED)
			return $val;

		switch ($tipo)
		{
			case CasterVal_Constants::Bool:
			case CasterVal_Constants::Boolean:
				$val = strtobool ($val);
				return (bool) $val;
				break;

			case CasterVal_Constants::Numero:
				$val = floatval ($val);
				return $val;
				break;

			case CasterVal_Constants::NumeroEntero:
			case CasterVal_Constants::Integer:
				$val = intval($val);
				return (int)$val;
				break;

			case CasterVal_Constants::Moneda:
				$val = floatval($val);
				return number_format($val, 2, '.', '');
				break;

			case CasterVal_Constants::Arreglo:
			case CasterVal_Constants::ArregloObjeto:
				if (is_string($val))
				{
					$json = json_decode($val, true);
					if ( ! is_null($json)) $val = $json;
				}

				return (array) $val;
				break;

			case CasterVal_Constants::Texto:
				return (string) $val;
				break;
		}

		is_array($val) and $val = json_encode($val);
		$val = (string) $val;

		$val = filter_apply('CasterVal/Convert/' . $tipo, $val);
		$val = filter_apply('ConvertVal/'        . $tipo, $val);

		return $val;
	}

	protected function _cv_onempty (?bool $nullable = true, $tipo = CasterVal_Constants::Texto)
	{
		return $nullable ? null : ( $tipo === CasterVal_Constants::Arreglo ? [] : '' );
	}

	protected function _cv_check_tipo ($valor, $tipo)
	{
		return self :: ConvertValToType ( $valor, $tipo );
	}

	protected function _cv_check_largo_max ($valor, $tipo, ?int $largo_req = 0, ?callable $on_error = null)
	{
		if (in_array($tipo, [ CasterVal_Constants::Arreglo, CasterVal_Constants::Boolean ])) return $valor;
		if (is_object($valor)) return $valor;
		if ($largo_req <= 0)   return $valor;

		$largo_act = mb_strlen((string) $valor);

		if ($largo_act <= $largo_req) return $valor;

		is_null($on_error) or call_user_func($on_error, 'Valor `' . $valor . '` muy largo, se ha procedido a truncarla a ' . $largo_req . ' caractéres');
		$valor = mb_substr($valor, 0, $largo_req);

		return $valor;
	}

	protected function _cv_check_in_array ($valor, $tipo, ?array $opciones = [], ?bool $nullable = true, ?callable $on_error = null)
	{
		if (is_empty($opciones)) return $valor;
		if (in_array($tipo, [ CasterVal_Constants::Boolean ])) return $valor;

		if (is_array($valor))
		{
			$valor = array_filter($valor, function($o) use ($opciones) {
				return in_array($o, $opciones);
			});
			return $valor;
		}

		if ( ! in_array($valor, $opciones))
		{
			is_null($on_error) or call_user_func($on_error, 'Valor `' . $valor . '` no autorizado, se ha procedido a omitirlo');
			$valor = $this -> _cv_onempty($nullable, $tipo);
		}
		
		return $valor;
	}
}
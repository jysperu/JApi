<?php

interface CasterVal_Constants 
{
	const MIXED         = 'MIXED';

	const Numero        = 'Numero';
	const NumeroEntero  = 'Integer';
	const Entero        = 'Integer';
	const Integer       = 'Integer';
	const Moneda        = 'Moneda';
	const Texto         = 'Texto';
	const Arreglo       = 'Array';
	const ArregloObjeto = 'Array De Objetos';
	const FechaHora     = 'FechaHora';
	const Fecha         = 'Fecha';
	const Hora          = 'Hora';
	const Boolean       = 'Boolean';
	const Bool          = 'Boolean';

	const Ilimitado = -1;

	public function CastVal ($indice, $valor = null);
}
<?php

if ( ! function_exists('is_command'))
{
	/**
	 * is_command()
	 * identifica si la solicitud de procedimiento ha sido por comando
	 * @return Boolean False en caso de que la solicitud ha sido por web.
	 */
	function is_command ()
	{
		return ISCOMMAND;
	}
}

if ( ! function_exists('is_cli'))
{
	/**
	 * is_cli()
	 */
	function is_cli ()
	{
		return ISCOMMAND;
	}
}

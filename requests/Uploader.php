<?php
namespace Request;

use UploadManager, Exception;

class Uploader
{
	public function POST_index ()
	{
		try
		{
			$Upload = new UploadManager ($_FILES['archivo']);
			addJSON((array)$Upload);
			return response_success('Archivo cargado correctamente');
		}
		catch (Exception $e)
		{
			return response_error($e->getMessage());
		}
	}
}
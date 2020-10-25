<?php
namespace Request;

use ImgManager;

class Img
{
	public function __construct ()
	{
		$filename = ImgManager::GetLocalProcessed(APP()->get_URI(), 'img');

		if (is_null($filename) or ! file_exists($filename))
		{
			http_code(404);
			return;
		}

		$filesize = @filesize($filename);

		$filepath = $filename;
		$filename = explode('/', str_replace(DS, '/', $filename));
		$filename = end($filename);
		$x = explode('.', $filename);
		$extension = end($x);
		$mime = get_mime($extension);

		if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT']))
		{
			$x[count($x) - 1] = strtoupper($extension);
			$filename = implode('.', $x);
		}

		if (($fp = @fopen($filepath, 'rb')) === FALSE)
		{
			http_code(404);
			return;
		}

		response_cache(7);
		ResponseAs('file');

		header('Content-Type: '.$mime);
//		header('Content-Disposition: attachment; filename="'.$filename.'"');
//		header('Expires: 0');
//		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.$filesize);
//		header('Cache-Control: private, no-transform, no-store, must-revalidate');

		// Flush 1MB chunks of data
		while ( ! feof($fp) && ($data = fread($fp, 1048576)) !== FALSE)
		{
			echo $data;
		}

		fclose($fp);
		exit;
	}
}

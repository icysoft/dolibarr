<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016   Jean-François Ferry     <jfefe@aternatik.fr>
 *
 * This program is free software you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;
use Luracast\Restler\Format\UploadFormat;


require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiSynchroGed {@requires user,external}
 */
class AvoloiSynchroGed extends DolibarrApi
{

	/**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
  }
  
	/**
	 * Download a document.
	 *
	 * Note that, this API is similar to using the wrapper link "documents.php" to download a file (used for
	 * internal HTML links of documents into application), but with no need to have a session cookie (the token is used instead).
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('facture', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
	 * @return  array                   List of documents
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /download
	 */
	public function syncIndex($original_file = '')
	{
		global $conf, $langs;

		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

		if (! file_exists($original_file_osencoded))
		{
			throw new RestException(404, 'File not found');
		}

		$file_content=file_get_contents($original_file_osencoded);

		return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($file_content), 'encoding'=>'base64' );
	}

	/**
	 * Return the list of all documents
	 *
	 * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
	 * @param	string	$sortorder		Sort order ('asc' or 'desc')
	 * @return	array					Array of documents with path
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url GET /all
	 */
	public function getDocumentsList($sortfield = '', $sortorder = '')
	{
		global $conf;
		$upload_dir = '/var/www/documents';

		$filearray = dol_dir_list($upload_dir, "all", 1, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1, 1);

		return $filearray;
	}

	/**
	 * Upload a file.
	 *
	 * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
	 *
	 * @param   string  $filename           Name of file to create ('FA1705-0123.txt')
	 * @param   string  $upload_dir        File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
	 * @param   int 	$overwriteifexists  Overwrite file if exists (1 by default)
     * @return  string
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url POST /upload
	 */
	public function syncPost($filename, $upload_dir, $filecontent = '', $fileencoding = '', $overwriteifexists = 0)
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->ecm->upload) {
			throw new RestException(401);
		}

		$newfilecontent = '';
		if (empty($fileencoding)) $newfilecontent = $filecontent;
		if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);

		$original_file = dol_sanitizeFileName($filename);

		// Define $uploadir
		$object = null;
		$entity = DolibarrApiAccess::$user->entity;

		$upload_dir = dol_sanitizePathName($upload_dir);

		$destfile = $upload_dir . '/' . $original_file;
		//$destfiletmp = DOL_DATA_ROOT.'/admin/temp/' . $original_file;
		$destfiletmp = '/tmp/' . $original_file;
		dol_delete_file($destfiletmp);
		//var_dump($original_file);exit;

		if (!dol_is_dir(dirname($destfile))) {
			mkdir(dirname($destfile), 0700, true);
		}

		if (! $overwriteifexists && dol_is_file($destfile))
		{
			throw new RestException(500, "File with name '".$original_file."' already exists.");
		}

		$fhandle = @fopen($destfiletmp, 'w');
		if ($fhandle)
		{
			$nbofbyteswrote = fwrite($fhandle, $newfilecontent);
			fclose($fhandle);
			@chmod($destfiletmp, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			throw new RestException(500, "Failed to open file '".$destfiletmp."' for write");
		}

		$result = dol_move($destfiletmp, $destfile, 0, $overwriteifexists, 1);

		if (! $result)
		{
			throw new RestException(500, "Failed to move file into '".$destfile."'");
		}

		return dol_basename($destfile);
	}

	/**
	 * Delete a file.
	 *
	 * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
	 *
	 * @param   string  $filename           Name of file to create ('FA1705-0123.txt')
	 * @param   string  $upload_dir        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @return  string
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url POST /delete
	 */
	public function syncDelete($filename, $upload_dir)
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->ecm->upload) {
			throw new RestException(401);
		}

		// Define $uploadir
		$object = null;
		$entity = DolibarrApiAccess::$user->entity;

		$upload_dir = dol_sanitizePathName($upload_dir);

		dol_delete_file($upload_dir . '/' . $filename);

		return dol_basename($$upload_dir . '/' . $filename);
	}


}

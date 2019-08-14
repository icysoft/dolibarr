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


require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class Docxgenerator {@requires user,external}
 */
class Docxgenerator extends DolibarrApi
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
	 * Build a document.
	 *
	 * Test sample 1: { "module_part": "invoice", "original_file": "FA1701-001/FA1701-001.pdf", "doctemplate": "crabe", "langcode": "fr_FR" }.
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('invoice', 'order', ...).
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf).
	 * @param	string	$doctemplate	Set here the doc template to use for document generation (If not set, use the default template).
	 * @param	string	$langcode		Language code like 'en_US', 'fr_FR', 'es_ES', ... (If not set, use the default language).
	 * @param	number	$idType			Id of the type to get, linked to $module_part. If $module_part is an invoice, idType should be the id of the invoice
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url PUT /builddocfromdocx
	 */
	public function builddocfromdocx($module_part, $original_file = '', $doctemplate = '', $langcode = '', $idType)
	{
		global $conf, $langs;

		if (empty($module_part)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}
		$name = explode("/", $original_file)[0];

		$outputlangs = $langs;
		if ($langcode && $langs->defaultlang != $langcode)
		{
			$outputlangs=new Translate('', $conf);
			$outputlangs->setDefaultLang($langcode);
		}

		//--- Finds and returns the document
		$entity=$conf->entity;

		$check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, '', 'write');
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
			throw new RestException(401);
		}
		if (!$accessallowed) {
			throw new RestException(401);
		}

		// --- Generates the document
		$hidedetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 0 : 1;
		$hidedesc = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 0 : 1;
		$hideref = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 0 : 1;

		$templateused='';
		$mimetype = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

		if ($module_part == 'facture' || $module_part == 'invoice')
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$this->invoice = new Facture($this->db);
			$result = $this->invoice->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Invoice not found');
			}
			$templateused = $doctemplate?$doctemplate:$this->invoice->modelpdf;
			if ($doctemplate == 'crabe') {
				$result = $this->invoice->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
				$mimetype = "application/pdf";
			} else {
				$result = $this->commonGenerateDocument('invoice', $templateused, $idType, $name, $hidedetails, $hidedesc, $hideref);
			}
			if (is_object($result)) {
				if ( $result->code <= 0 ) {
					throw new RestException(500, 'Error generating document');
				}
				// $filename = basename($original_file);
				// return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($result->b64content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
			}
			else
			if (is_array($result)) {
				if ( $result['code'] <= 0 ) {
					throw new RestException(500, 'Error generating document');
				}
				// $filename = basename($original_file);
				// return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($result->b64content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
			}
			else
			if ( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		elseif ($module_part == 'propal' || $module_part == 'proposal')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$this->propal = new Propal($this->db);
			$result = $this->propal->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Proposal not found');
			}
			$templateused = $doctemplate?$doctemplate:$this->propal->modelpdf;

			if ($doctemplate == 'azur') {
				$result = $this->propal->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
				$mimetype = "application/pdf";
			} else {
				$result = $this->commonGenerateDocument('proposal', $templateused, $idType, $name, $hidedetails, $hidedesc, $hideref);
			}
			if (is_object($result)) {
				if ( $result->code <= 0 ) {
					throw new RestException(500, 'Error generating document');
				}

				$filename = basename($original_file);
				return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($result->b64content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
			}
			else
			if ( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		elseif ($module_part == 'projet' || $module_part == 'project' || $module_part == 'affaire')
		{
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$this->project = new Project($this->db);
			$result = $this->project->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Proposal not found');
			}
			$templateused = $doctemplate?$doctemplate:$this->project->modelpdf;

			if ($doctemplate == 'baleine') {
				$result = $this->project->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
				$mimetype = "application/pdf";
			} else {
				$result = $this->commonGenerateDocument('project', $templateused, $idType, $name, $hidedetails, $hidedesc, $hideref);
			}
			if (is_object($result)) {
				if ( $result->code <= 0 ) {
					throw new RestException(500, 'Error generating document');
				}

				$filename = basename($original_file);
				return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($result->b64content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
			}
			else
			if ( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		else
		{
			throw new RestException(403, 'Generation not available for this modulepart');
		}
		if (is_array($result)) {
			if ($result['documentPath']) {
				$original_file = $result['documentPath'];
			}
		}
		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset
		// print_r($original_file_osencoded);
		// if (! file_exists($original_file_osencoded))
		// {
		// 	throw new RestException(404, 'File not found');
		// }

		$file_content=file_get_contents($this->sanitizePath($original_file_osencoded));
		return array('filename'=>$filename, 'content-type' => $mimetype, 'filesize'=>filesize($this->sanitizePath($original_file_osencoded)), 'content'=>base64_encode($file_content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
	}

	public function sanitizePath($str) {
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		return strtr($str , $unwanted_array );
	}

	/**
	 * Common function for all objects extending CommonObject for generating documents
	 *
	 * @param 	string 		$modelspath 	Relative folder where generators are placed
	 * @param 	string 		$modele 		Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf') for example.
	 * @param 	int 		$hidedetails 	1 to hide details. 0 by default
	 * @param 	int 		$hidedesc 		1 to hide product description. 0 by default
	 * @param 	int 		$hideref 		1 to hide product reference. 0 by default
	 * @param   null|array  $moreparams     Array to provide more information
	 * @return 	int 						>0 if OK, <0 if KO
	 * @see	addFileIntoDatabaseIndex()
	 */
	protected function commonGenerateDocument($typeDocument, $modele, $idType, $name, $hidedetails, $hidedesc, $hideref, $moreparams = null)
	{
		global $conf, $langs, $user;

		$srctemplatepath='';

		// Increase limit for PDF build
		$err=error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);
		

		// If selected model is a filename template (then $modele="modelname" or "modelname:filename")
		// $tmp=explode(':', $modele, 2);
		
		// if (! empty($tmp[1]))
		// {
		// 	$modele=$tmp[0];
		// 	$srctemplatepath=$tmp[1];
		// }

		// Search template files
		// $file=''; $classname=''; $filefound=0;
		// $dirmodels=array('/');
		// if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels, $conf->modules_parts['models']);
		// foreach($dirmodels as $reldir)
		// {
		// 	foreach(array('doc','pdf') as $prefix)
		// 	{
		// 		if (in_array(get_class($this), array('Adherent'))) $file = $prefix."_".$modele.".class.php";     // Member module use prefix_module.class.php
		// 		else $file = $prefix."_".$modele.".modules.php";

		// 		// On verifie l'emplacement du modele
		// 		$file=dol_buildpath($reldir.$modelspath.$file, 0);
		// 		if (file_exists($file))
		// 		{
		// 			$filefound=1;
		// 			$classname=$prefix.'_'.$modele;
		// 			break;
		// 		}
		// 	}
		// 	if ($filefound) break;
		// }

		// If generator was found
		// if ($filefound)
		// {
			global $db;  // Required to solve a conception default in commonstickergenerator.class.php making an include of code using $db

			// require_once $file;

			// $obj = new $classname($this->db);

			// If generator is ODT, we must have srctemplatepath defined, if not we set it.
			// if ($obj->type == 'odt' && empty($srctemplatepath))
			// {
			// 	$varfortemplatedir=$obj->scandir;
			// 	if ($varfortemplatedir && ! empty($conf->global->$varfortemplatedir))
			// 	{
			// 		$dirtoscan=$conf->global->$varfortemplatedir;

			// 		$listoffiles=array();

			// 		// Now we add first model found in directories scanned
			// 		$listofdir=explode(',', $dirtoscan);
			// 		foreach($listofdir as $key => $tmpdir)
			// 		{
			// 			$tmpdir=trim($tmpdir);
			// 			$tmpdir=preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			// 			if (! $tmpdir) { unset($listofdir[$key]); continue; }
			// 			if (is_dir($tmpdir))
			// 			{
			// 				$tmpfiles=dol_dir_list($tmpdir, 'files', 0, '\.od(s|t)$', '', 'name', SORT_ASC, 0);
			// 				if (count($tmpfiles)) $listoffiles=array_merge($listoffiles, $tmpfiles);
			// 			}
			// 		}

			// 		if (count($listoffiles))
			// 		{
			// 			foreach($listoffiles as $record)
			// 			{
			// 				$srctemplatepath=$record['fullname'];
			// 				break;
			// 			}
			// 		}
			// 	}

			// 	if (empty($srctemplatepath))
			// 	{
			// 		$this->error='ErrorGenerationAskedForOdtTemplateWithSrcFileNotDefined';
			// 		return -1;
			// 	}
			// }

			// if ($obj->type == 'odt' && ! empty($srctemplatepath))
			// {
			// 	if (! dol_is_file($srctemplatepath))
			// 	{
			// 		$this->error='ErrorGenerationAskedForOdtTemplateWithSrcFileNotFound';
			// 		return -1;
			// 	}
			// }

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			// TODO : remettre en paramètre
			$sav_charset_output='UTF8';
			$outputlangs=new Translate('', $conf);
			$outputlangs->setDefaultLang('fr_FR');

			// if (in_array(get_class($this), array('Adherent')))
			// {
			// 	$arrayofrecords = array();   // The write_file of templates of adherent class need this var
			// 	$resultwritefile = $obj->write_file($document, $outputlangs, $srctemplatepath, 'member', 1, $moreparams);
			// }
			// else
			// {
				require_once DOL_DOCUMENT_ROOT.'/docxgenerator/class/docx_generator.modules.php';
				$docxgenerator=new docx_generator($this->db);
				$resultwritefile = $docxgenerator->write_file($typeDocument, $idType, $modele, $name, $outputlangs, '', $hidedetails, $hidedesc, $hideref, $moreparams);
				// $resultwritefile = $obj->write_file($document, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $moreparams);
			// }
			// After call of write_file $obj->result['fullpath'] is set with generated file. It will be used to update the ECM database index.
			if ($resultwritefile['code'] > 0) {
				return $resultwritefile;
			} else
			if ($resultwritefile > 0)
			{
				$outputlangs->charset_output=$sav_charset_output;

				// We delete old preview
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_delete_preview($this);

				// Index file in database
				if (! empty($obj->result['fullpath']))
				{
					$destfull = $obj->result['fullpath'];
					$upload_dir = dirname($destfull);
					$destfile = basename($destfull);
					$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);

					if (! preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $rel_dir))     // If not a tmp dir
					{
						$filename = basename($destfile);
						$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
						$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

						include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
						$ecmfile=new EcmFiles($this->db);
						$result = $ecmfile->fetch(0, '', ($rel_dir?$rel_dir.'/':'').$filename);

						// Set the public "share" key
						$setsharekey = false;
						if ($this->element == 'propal')
						{
							$useonlinesignature = $conf->global->MAIN_FEATURES_LEVEL;	// Replace this with 1 when feature to make online signature is ok
							if ($useonlinesignature) $setsharekey=true;
							if (! empty($conf->global->PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD)) $setsharekey=true;
						}
						if ($this->element == 'commande' && ! empty($conf->global->ORDER_ALLOW_EXTERNAL_DOWNLOAD)) {
							$setsharekey=true;
						}
						if ($this->element == 'facture' && ! empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD)) {
							$setsharekey=true;
						}
						if ($this->element == 'bank_account' && ! empty($conf->global->BANK_ACCOUNT_ALLOW_EXTERNAL_DOWNLOAD)) {
							$setsharekey=true;
						}

						if ($setsharekey)
						{
							if (empty($ecmfile->share))	// Because object not found or share not set yet
							{
								require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
								$ecmfile->share = getRandomPassword(true);
							}
						}

						if ($result > 0)
						{
							$ecmfile->label = md5_file(dol_osencode($destfull));	// hash of file content
							$ecmfile->fullpath_orig = '';
							$ecmfile->gen_or_uploaded = 'generated';
							$ecmfile->description = '';    // indexed content
							$ecmfile->keyword = '';        // keyword content
							$result = $ecmfile->update($user);
							if ($result < 0)
							{
								setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
							}
						}
						else
						{
							$ecmfile->entity = $conf->entity;
							$ecmfile->filepath = $rel_dir;
							$ecmfile->filename = $filename;
							$ecmfile->label = md5_file(dol_osencode($destfull));	// hash of file content
							$ecmfile->fullpath_orig = '';
							$ecmfile->gen_or_uploaded = 'generated';
							$ecmfile->description = '';    // indexed content
							$ecmfile->keyword = '';        // keyword content
							$ecmfile->src_object_type = $this->table_element;
							$ecmfile->src_object_id   = $this->id;

							$result = $ecmfile->create($user);
							if ($result < 0)
							{
								setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
							}
						}

						/*$this->result['fullname']=$destfull;
						$this->result['filepath']=$ecmfile->filepath;
						$this->result['filename']=$ecmfile->filename;*/
						//var_dump($obj->update_main_doc_field);exit;

						// Update the last_main_doc field into main object (if documenent generator has property ->update_main_doc_field set)
						$update_main_doc_field=0;
						if (! empty($obj->update_main_doc_field)) $update_main_doc_field=1;
						if ($update_main_doc_field && ! empty($this->table_element))
						{
							$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element." SET last_main_doc = '".$this->db->escape($ecmfile->filepath.'/'.$ecmfile->filename)."'";
							$sql.= ' WHERE rowid = '.$this->id;

							$resql = $this->db->query($sql);
							if (! $resql) dol_print_error($this->db);
							else
							{
							    $this->last_main_doc = $ecmfile->filepath.'/'.$ecmfile->filename;
							}
						}
					}
				}
				else
				{
					dol_syslog('Method ->write_file was called on object '.get_class($obj).' and return a success but the return array ->result["fullpath"] was not set.', LOG_WARNING);
				}

				// Success in building document. We build meta file.
				dol_meta_create($this);
				return 1;
			}
			else
			{
				$outputlangs->charset_output=$sav_charset_output;
				dol_print_error($this->db, "Error generating document for ".__CLASS__.". Error: ".$obj->error, $obj->errors);
				return -1;
			}
		// }
		// else
		// {
		// 	$this->error=$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $file);
		// 	dol_print_error('', $this->error);
		// 	return -1;
		// }
	}

	

	/**
	 * Return the list of documents of a dedicated element (from its ID or Ref)
	 *
	 * @param   string 	$modulepart		Name of module or area concerned ('thirdparty', 'member', 'proposal', 'order', 'invoice', 'shipment', 'project',  ...)
	 * @param	int		$id				ID of element
	 * @param	int		$tiersid		ID of element
	 * @param	string	$ref			Ref of element
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
	 * @url GET /getDocumentsListByElement
	 */
	public function getDocumentsListByElement($modulepart, $id = 0, $tiersid = 0, $ref = '', $sortfield = '', $sortorder = '')
	{
		global $conf;

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}

		if (empty($id) && empty($ref)) {
			throw new RestException(400, 'bad value for parameter id or ref');
		}

		$id = (empty($id)?0:$id);

		if ($modulepart == 'societe' || $modulepart == 'thirdparty')
		{
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

			if (!DolibarrApiAccess::$user->rights->societe->lire) {
				throw new RestException(401);
			}

			$object = new Societe($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Thirdparty not found');
			}

			$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id;
		}
		elseif ($modulepart == 'adherent' || $modulepart == 'member')
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

			if (!DolibarrApiAccess::$user->rights->adherent->lire) {
				throw new RestException(401);
			}

			$object = new Adherent($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Member not found');
			}

			$upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');
		}
		elseif ($modulepart == 'propal' || $modulepart == 'proposal')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

			if (!DolibarrApiAccess::$user->rights->propal->lire) {
				throw new RestException(401);
			}

			$object = new Propal($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Proposal not found');
			}

			$upload_dir = $conf->propal->multidir_output[$object->entity] . "/" . get_exdir(0, 0, 0, 1, $object, 'propal');
		}
		elseif ($modulepart == 'commande' || $modulepart == 'order')
		{
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

			if (!DolibarrApiAccess::$user->rights->commande->lire) {
				throw new RestException(401);
			}

			$object = new Commande($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Order not found');
			}

			$upload_dir = $conf->commande->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'commande');
		}
		elseif ($modulepart == 'shipment' || $modulepart == 'expedition')
		{
			require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

			if (!DolibarrApiAccess::$user->rights->expedition->lire) {
				throw new RestException(401);
			}

			$object = new Expedition($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Shipment not found');
			}

			$upload_dir = $conf->expedition->dir_output . "/sending/" . get_exdir(0, 0, 0, 1, $object, 'shipment');
		}
		elseif ($modulepart == 'facture' || $modulepart == 'invoice')
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

			if (!DolibarrApiAccess::$user->rights->facture->lire) {
				throw new RestException(401);
			}

			$object = new Facture($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Invoice not found');
			}

			$upload_dir = $conf->facture->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'invoice');
		}
        elseif ($modulepart == 'produit' || $modulepart == 'product')
		{
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

			if (!DolibarrApiAccess::$user->rights->produit->lire) {
				throw new RestException(401);
			}

			$object = new Product($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Product not found');
			}

			$upload_dir = $conf->product->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'product');
		}
		elseif ($modulepart == 'agenda' || $modulepart == 'action' || $modulepart == 'event')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

			if (!DolibarrApiAccess::$user->rights->agenda->myactions->read && !DolibarrApiAccess::$user->rights->agenda->allactions->read) {
				throw new RestException(401);
			}

			$object = new ActionComm($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Event not found');
			}

			$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
		}
		elseif ($modulepart == 'project')
		{
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

			if ($id) {
				$sql = "SELECT *";
				$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
				$sql .= " WHERE p.rowid = ".$id;

				$result = $this->db->query($sql);
				$affaire = $this->db->fetch_object($result);
			}

			if ($tiersid) {
				$sql = "SELECT *";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe as p";
				$sql .= " WHERE p.rowid = ".$tiersid;

				$result = $this->db->query($sql);
				$tiers = $this->db->fetch_object($result);
			}
			// $result=$object->fetch($id, $ref);
			// if ( ! $result ) {
			// 	throw new RestException(404, 'Le projet ne contient aucun document');
			// }
			$upload_dir = $this->hardsanitizePath(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/affaire'.'/'.$affaire->title);
			// $upload_dir = $conf->projet->dir_output.'/' .$object->id;
		}
		elseif (strpos($modulepart, 'doctemplates/') !== false)
		{
			$upload_dir = '/var/www/documents/'.$modulepart;
		}
		else
		{
			throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
		}

		$filearray=dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1);
		if (empty($filearray)) {
			throw new RestException(404, 'Search for modulepart '.$modulepart.' with Id '.$object->id.(! empty($object->Ref)?' or Ref '.$object->ref:'').' does not return any document.');
		}

		return $filearray;
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
	public function index($module_part, $original_file = '')
	{
		global $conf, $langs;

		if (empty($module_part)) {
				throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		//--- Finds and returns the document
		$entity=$conf->entity;

		$check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, '', 'read');
		$accessallowed = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		// $original_file = $check_access['original_file'];

		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
			throw new RestException(401);
		}
		if (!$accessallowed) {
			throw new RestException(401);
		}
		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

		if (! file_exists($original_file_osencoded))
		{
			throw new RestException(404, 'File not found');
		}

		$file_content=file_get_contents($original_file_osencoded);

		//print "ORIGINAL FILE : $original_file<br>";
		return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($file_content), 'encoding'=>'base64' );
	}

	protected function hardsanitizePath($str) {
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', '/\s+/'=>'_', ' '=>'_' );
		return strtr($str , $unwanted_array );
	}
}

<?php
/* Copyright (C) 2010-2011 Laurent Destailleur <ely@users.sourceforge.net>
 * Copyright (C) 2016	   Charlie Benke	   <charlie@patas-monkey.com>
 * Copyright (C) 2018      Frédéric France     <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/docxgenerator/class/docx_generator.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build Docx documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpword/bootstrap.php';


/**
 *	Class to build documents using ODF templates generator
 */
class docx_generator extends ModeleThirdPartyDoc
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
	public $phpmin = array(5, 5);


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
        $langs->loadLangs(array("main","companies"));

		$this->db = $db;
		$this->name = "DOCX templates";
		$this->description = $langs->trans("DocumentModelDocx");
		$this->scandir = 'COMPANY_ADDON_PDF_DOCX_PATH';	// Name of constant that is used to save list of directories to scan

		// Dimension page pour format A4
		$this->type = 'docx';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=0;
		$this->marge_droite=0;
		$this->marge_haute=0;
		$this->marge_basse=0;

		$this->option_logo = 1;                    // Affiche logo

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($typeDocument, $idType, $templateName, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
        // phpcs:enable
        global $user,$langs,$conf,$mysoc,$hookmanager;

		// if (empty($srctemplatepath))
		// {
		// 	dol_syslog("doc_generic_docx::write_file parameter srctemplatepath empty", LOG_WARNING);
		// 	return -1;
        // }

                // Add odtgeneration hook
                if (! is_object($hookmanager))
                {
                        include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                        $hookmanager=new HookManager($this->db);
                }
                $hookmanager->initHooks(array('odtgeneration'));
                global $action;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='UTF-8';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "projects"));

		// if ($conf->societe->multidir_output[$object->entity])
		// {
		// 	$dir = $conf->societe->multidir_output[$object->entity];
		// 	$objectref = dol_sanitizeFileName($object->id);
		// 	if (! preg_match('/specimen/i', $objectref)) $dir.= "/" . $objectref;

		// 	if (! file_exists($dir))
		// 	{
		// 		if (dol_mkdir($dir) < 0)
		// 		{
		// 			$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
		// 			return -1;
		// 		}
		// 	}

		// 	if (file_exists($dir))
		// 	{
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile=basename($srctemplatepath);
				$newfiletmp=preg_replace('/\.docx/i', '', $newfile);
				$newfiletmp=preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp=preg_replace('/modele_/i', '', $newfiletmp);
				// Get extension (ods or odt)
				$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
				if ( ! empty($conf->global->MAIN_DOC_USE_OBJECT_THIRDPARTY_NAME))
				{
				    $newfiletmp = 'test-'.$newfiletmp;
				    // $newfiletmp = dol_sanitizeFileName(dol_string_nospecial($object->name)).'-'.$newfiletmp;
				}
				if ( ! empty($conf->global->MAIN_DOC_USE_TIMING))
				{
				    $format=$conf->global->MAIN_DOC_USE_TIMING;
				    if ($format == '1') $format='%Y%m%d%H%M%S';
					$filename=$newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;
				}
				else
				{
					$filename=$newfiletmp.'.'.$newfileformat;
				}
				$file=$dir.'/'.$filename;
				// $object->builddoc_filename=$filename; // For triggers
				//print "newfileformat=".$newfileformat;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;
				//exit;

				// dol_mkdir($conf->societe->multidir_temp[$object->entity]);

                // Open and load template
                $phpWord = new \PhpOffice\PhpWord\PhpWord();
				try {
					switch ($typeDocument) {
						case 'invoice':
							$template = DOL_DOCUMENT_ROOT.'/docxgenerator/templates/invoice/'.$templateName;
							break;
						case 'acte':
							$template = DOL_DOCUMENT_ROOT.'/docxgenerator/templates/acte/'.$templateName;
							break;
						case 'proposal':
							$template = DOL_DOCUMENT_ROOT.'/docxgenerator/templates/proposal/'.$templateName;
							break;
						case 'project':
							$template = DOL_DOCUMENT_ROOT.'/docxgenerator/templates/project/'.$templateName;
							break;
					}
                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template);
				    // require_once ODTPHP_PATH.'odf.php';
                    // $odfHandler = new odf(
					//     $srctemplatepath,
					//     array(
	    			// 		'PATH_TO_TMP'	  => $conf->societe->multidir_temp[$object->entity],
	    			// 		'ZIP_PROXY'		  => 'PclZipProxy',	// PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
	    			// 		'DELIMITER_LEFT'  => '{',
	    			// 		'DELIMITER_RIGHT' => '}'
					// 	)
					// );
				}
				catch(Exception $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
				//print $odfHandler->__toString()."\n";

                // Replace tags of lines for contacts
				$contact_arrray=array();
				
				// On récupère l'objet correspondant en fonction du typeDocument
				switch ($typeDocument) {
					case 'invoice':
						$sql = "SELECT *";
						$sql .= " FROM ".MAIN_DB_PREFIX."facture as p";
						$sql .= " WHERE p.rowid = ".$idType;

						$result = $this->db->query($sql);
						break;
					case 'acte':
						$sql = "SELECT *";
						$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
						$sql .= " WHERE p.rowid = ".$idType;

						$result = $this->db->query($sql);
						break;
					case 'proposal':
						$sql = "SELECT *";
						$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
						$sql .= " WHERE p.rowid = ".$idType;

						$result = $this->db->query($sql);
						break;
					case 'project':
						$sql = "SELECT *";
						$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
						$sql .= " WHERE p.rowid = ".$idType;

						$result = $this->db->query($sql);
						break;
				}
				$object = $this->db->fetch_object($result);

                $sql = "SELECT *";
                $sql .= " FROM ".MAIN_DB_PREFIX."societe as p";
                $sql .= " WHERE p.rowid = ".$object->fk_soc;

                $result = $this->db->query($sql);
				$tiers = $this->db->fetch_object($result);

                $sql = "SELECT p.rowid";
                $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
                $sql .= " WHERE p.fk_soc = ".$object->id;

                $result = $this->db->query($sql);
                $num = $this->db->num_rows($result);

                if ($num)
                {
                    require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

                	$i=0;
                	$contactstatic = new Contact($this->db);

                	while($i < $num)
                	{
                		$obj = $this->db->fetch_object($result);

                		$contact_arrray[$i] = $obj->rowid;
                		$i++;
                	}
				}

                // TODO : Vérifier existence des segments

                // if((is_array($contact_arrray) && count($contact_arrray) > 0))
                // {
                // 	try
                // 	{
                // 		$listlines = $templateProcessor->setSegment('companycontacts');

                // 		foreach($contact_arrray as $array_key => $contact_id)
                // 		{
                // 			$res_contact = $contactstatic->fetch($contact_id);
                // 			$tmparray=$this->get_substitutionarray_contact($contactstatic, $outputlangs, 'contact');
                // 			foreach($tmparray as $key => $val)
                // 			{
                // 				try
                // 				{
                // 					$listlines->setValue($key, $val);
                // 				}
                // 				catch(OdfException $e)
                // 				{
				// 					dol_syslog($e->getMessage(), LOG_INFO);
                // 				}
                // 				catch(SegmentException $e)
                // 				{
				// 					dol_syslog($e->getMessage(), LOG_INFO);
                // 				}
                // 			}
                // 			$listlines->merge();
                // 		}
                // 		$odfHandler->mergeSegment($listlines);
                // 	}
                // 	catch(OdfException $e)
                // 	{
                // 		$this->error=$e->getMessage();
                // 		dol_syslog($this->error, LOG_WARNING);
                // 		//return -1;
                // 	}
                // }

                // Make substitutions into odt
                $array_user=$this->get_substitutionarray_user($user, $outputlangs);
                $array_soc=$this->get_substitutionarray_mysoc($mysoc, $outputlangs);
                $array_thirdparty=$this->get_substitutionarray_thirdparty($object, $outputlangs);
				$array_other=$this->get_substitutionarray_other($outputlangs);

                $tmparray = array_merge($array_user, $array_soc, $array_thirdparty, $array_other);
                complete_substitutions_array($tmparray, $outputlangs, $object);

                // Call the ODTSubstitution hook
                $parameters=array('odfHandler'=>&$templateProcessor,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                // Replace variables into document
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/', $key))	// Image
						{
							if (file_exists($value)) $templateProcessor->setImageValue($key, $value);
							else $templateProcessor->setValue($key, 'ErrorFileNotFound');
						}
						else	// Text
						{
							$templateProcessor->setValue($key, $value);
                        }
                        // $templateProcessor->setValue($key, $value);
					}
					catch (OdfException $e)
					{
						// setValue failed, probably because key not found
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
                }

				// Replace labels translated
				$tmparray=$outputlangs->get_translations_for_substitutions();
				foreach($tmparray as $key=>$value)
				{
					try {
						$templateProcessor->setValue($key, $value);
					}
					catch (OdfException $e)
					{
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Call the beforeODTSave hook
				$parameters=array('odfHandler'=>&$templateProcessor,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try {
						$templateProcessor->exportAsAttachedPDF($file);
					} catch (Exception $e) {
						$this->error=$e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				else {
				    try {
                        $properties = $phpWord->getDocInfo();
                        $properties->setCreator($user->getFullName($outputlangs));
                        $properties->setTitle($object->builddoc_filename);
                        $properties->setSubject($object->builddoc_filename);

                        if (! empty($conf->global->ODT_ADD_DOLIBARR_ID))
                        {
                            $templateProcessor->userdefined['dol_id'] = $object->id;
                            $templateProcessor->userdefined['dol_element'] = $object->element;
						}
						switch ($typeDocument) {
							case 'invoice':
								if (!dol_is_dir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/facture')) {
									mkdir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/facture', 0700, true);
								}
								$storage = 'facture/'.$idType.'_'.time().'_'. $templateName;
								break;
							case 'acte':
								if (!dol_is_dir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/affaire')) {
									mkdir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/affaire', 0700, true);
								}
								$storage = 'affaire/'.$idType.'_'.time().'_'. $templateName;
								break;
							case 'proposal':
								if (!dol_is_dir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/propale')) {
									mkdir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/propale', 0700, true);
								}
								$storage = 'propale/'.$idType.'_'.time().'_'. $templateName;
								break;
							case 'project':
								if (!dol_is_dir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/affaire')) {
									mkdir(DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/affaire', 0700, true);
								}
								$storage = 'affaire/'.$idType.'_'.time().'_'. $templateName;
								break;
						}
						$path = DOL_DATA_ROOT.'/tiers/'.$tiers->nom.'/'.$storage;
                        $newtmpfile = $templateProcessor->saveAs($path);
                        // $file = $phpWord->save('invoice.docx', 'Word2007');
                        //    $templateProcessor->saveToDisk($file);
					} catch (Exception $e){
						$this->error=$e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				$parameters=array('odfHandler'=>&$templateProcessor,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				// if (! empty($conf->global->MAIN_UMASK))
				// @chmod($file, octdec($conf->global->MAIN_UMASK));

				$templateProcessor=null;	// Destroy object
                $phpWord=null;	// Destroy object
                return array('code'=> 1, 'documentPath'=>$path);
				// $this->result = array('fullpath'=>$file);

				// return 1;   // Success
		// 	}
		// 	else
		// 	{
		// 		$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
		// 		return -1;
		// 	}
		// }

		$this->error='UnknownError';
		return -1;
	}
}

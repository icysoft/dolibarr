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
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/core/modules/societe/modules_societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/phpword/bootstrap.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/avoloidivers/class/api_avoloidivers.class.php';
require_once DOL_DOCUMENT_ROOT . '/main.inc.php';


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
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "DOCX templates";
		$this->description = $langs->trans("DocumentModelDocx");
		$this->scandir = 'COMPANY_ADDON_PDF_DOCX_PATH';	// Name of constant that is used to save list of directories to scan

		// Dimension page pour format A4
		$this->type = 'docx';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1;                    // Affiche logo

		// Recupere emmetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini
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
	public function write_file($typeDocument, $idType, $templateName, $name, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		// Add odtgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "projects"));

		$newfile = basename($srctemplatepath);
		$newfiletmp = preg_replace('/\.docx/i', '', $newfile);
		$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
		$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);

		// Get extension (ods or odt)
		$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
		if (!empty($conf->global->MAIN_DOC_USE_OBJECT_THIRDPARTY_NAME)) {
			$newfiletmp = 'test-' . $newfiletmp;
			// $newfiletmp = dol_sanitizeFileName(dol_string_nospecial($object->name)).'-'.$newfiletmp;
		}
		if (!empty($conf->global->MAIN_DOC_USE_TIMING)) {
			$format = $conf->global->MAIN_DOC_USE_TIMING;
			if ($format == '1') $format = '%Y%m%d%H%M%S';
			$filename = $newfiletmp . '-' . dol_print_date(dol_now(), $format) . '.' . $newfileformat;
		} else {
			$filename = $newfiletmp . '.' . $newfileformat;
		}
		$file = $dir . '/' . $filename;

		// Open and load template
		$phpWord = new \PhpOffice\PhpWord\PhpWord();

		try {
			switch ($typeDocument) {
				case 'invoice':
					$template = DOL_DATA_ROOT . '/doctemplates/invoices/' . $templateName;
					break;
				case 'acte':
					$template = DOL_DATA_ROOT . '/doctemplates/acte/' . $templateName;
					break;
				case 'proposal':
					$template = DOL_DATA_ROOT . '/doctemplates/proposals/' . $templateName;
					break;
				case 'project':
					$template = DOL_DATA_ROOT . '/doctemplates/projects/' . $templateName;
					break;
			}
			$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return -1;
		}

		// Replace tags of lines for contacts
		$contact_arrray = array();

		// On récupère l'objet correspondant en fonction du typeDocument
		switch ($typeDocument) {
			case 'invoice':
				// $sql = "SELECT *";
				// $sql .= " FROM ".MAIN_DB_PREFIX."facture as p";
				// $sql .= " WHERE p.rowid = ".$idType;

				// $result = $this->db->query($sql);
				// $object = $this->db->fetch_object($result);
				// $sql = "SELECT *";
				// $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as p";
				// $sql .= " WHERE p.fk_facture = ".$object->rowid;

				// $result = $this->db->query($sql);

				$object = $this->getInvoice($idType);

				if ($object->mode_reglement_id && $object->mode_reglement_id == 2 && $object->fk_account) {
					$bankAccount = $this->getBankAccount($object->fk_account);
				}
				break;
				// ACTE n'est actuellement pas utilisé. PROJECT le remplace
				// case 'acte':
				// 	$sql = "SELECT *";
				// 	$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
				// 	$sql .= " WHERE p.rowid = ".$idType;

				// 	$result = $this->db->query($sql);
				// 	$object = $this->db->fetch_object($result);
				// 	break;
			case 'proposal':
				$object = $this->getProposal($idType);

				if ($object->array_options && $object->array_options['options_multitiers']) {
					$object->array_options['options_multitiers'] = json_decode($object->array_options['options_multitiers']);
				}
				if (is_array($object->array_options['options_multitiers'])) {
					foreach ($object->array_options['options_multitiers'] as $tiersFromMulti) {
						$societe = $this->getSociety($tiersFromMulti->idTiers);
					}
				}
				break;
			case 'project':
				$object = $this->getAffaire($idType);

				if ($object->array_options && $object->array_options['options_multitiers']) {
					$object->array_options['options_multitiers'] = json_decode($object->array_options['options_multitiers']);

					foreach ($object->array_options['options_multitiers'] as $tiersFromMulti) {
						$societe = $this->getSociety($tiersFromMulti->idTiers);
					}
				}
				break;
		}

		if ($object->fk_soc) {
			$tiers = $this->getSociety($object->fk_soc);
		}

		if ($object->socid) {
			$tiers = $this->getSociety($object->socid);
		}

		if ($object->fk_projet) {
			$affaire = $this->getAffaire($object->fk_projet);

			if ($affaire->array_options && $affaire->array_options['options_multitiers']) {
				$affaire->array_options['options_multitiers'] = json_decode($affaire->array_options['options_multitiers']);
			}

			foreach ($affaire->array_options['options_multitiers'] as $tiersAffaire) {
				$tiersAffaire->detail = $this->getSociety($tiersAffaire->idTiers);
			}
		}

		if ($object->fk_project) {
			$affaire = $this->getAffaire($object->fk_project);

			if ($affaire->array_options && $affaire->array_options['options_multitiers']) {
				$affaire->array_options['options_multitiers'] = json_decode($affaire->array_options['options_multitiers']);
			}
			if ($affaire->array_options['options_multitiers'] && is_array($affaire->array_options['options_multitiers'])) {
				foreach ($affaire->array_options['options_multitiers'] as $tiersAffaire) {
					$tiersAffaire->detail = $this->getSociety($tiersAffaire->idTiers);
				}
			}
		}

		// Code pour récupérer les contacts associés à un tiers
		// N'utilisant pas les contacts actuellement le code est commenté.
		// $sql = "SELECT p.rowid";
		// $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
		// $sql .= " WHERE p.fk_soc = ".$object->id;

		// $result = $this->db->query($sql);
		// $num = $this->db->num_rows($result);

		// if ($num)
		// {
		// 	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		// 	$i=0;
		// 	$contactstatic = new Contact($this->db);

		//   while($i < $num)
		//   {
		//     $obj = $this->db->fetch_object($result);

		//     $contact_arrray[$i] = $obj->rowid;
		//     $i++;
		//   }
		// }

		// TODO : Vérifier existence des segments

		// Make substitutions into odt
		$array_user = $this->get_substitutionarray_user($user, $outputlangs);
		$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
		$array_thirdparty = $this->get_substitutionarray_thirdparty($object, $outputlangs);
		$array_other = $this->get_substitutionarray_other($outputlangs);

		$tmparray = array_merge($array_user, $array_soc, $array_thirdparty, $array_other);
		complete_substitutions_array($tmparray, $outputlangs, $object);

		// Call the ODTSubstitution hook
		$parameters = array('odfHandler' => &$templateProcessor, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
		$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

		// Replace variables into document
		foreach ($tmparray as $key => $value) {
			try {
				if (preg_match('/logo$/', $key))	// Image
				{
					// TODO NON FONCTIONNEL !!
					// if (file_exists($value)) $templateProcessor->setImageValue($key, $value);
					// else $templateProcessor->setValue($key, 'ErrorFileNotFound');
				} else	// Text
				{
					$templateProcessor->setValue($key, $value);
				}
			} catch (OdfException $e) {
				// setValue failed, probably because key not found
				dol_syslog($e->getMessage(), LOG_INFO);
			}
		}

		if ($object->array_options && $object->array_options['options_multitiers']) {
			$object->array_options['options_multitiers'] = json_decode($object->array_options['options_multitiers']);

			foreach ($object->array_options['options_multitiers'] as $tiersFromMulti) {
				// Récupération du tiers grace à son ID
				$avoDivers = new AvoloiDivers($this->db);
				$keys = $avoDivers->getSociety($tiersFromMulti->idTiers);
				$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_type', $tiersFromMulti->typeTiers);

				foreach ($keys as $key => $value) {
					if (preg_match('/logo$/', $key))	// Image
					{
						if (file_exists($value)) $templateProcessor->setImageValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, $value);
						else $templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, 'ErrorFileNotFound');
					} else {
						if (is_object($value) || is_array($value)) {
							if ($key === 'array_options') {
								foreach ($value as $key_array => $value_array) {
									$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key_array, $value_array);
								}

								// Pour variabiliser le contact principal
								if ($value["options_primary_contact"]) {
									// Get contact by ID
									$contact = $this->getContact($value["options_primary_contact"]);
									foreach ($contact as $keyContact => $valueContact) {
										// Set primary contact in tiers
										if (!is_object($valueContact) && !is_array($valueContact))
											$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_primary_contact_' . $keyContact, $valueContact);
									}
								}

								// Pour variabiliser les autres contacts
								$contact = new AvoloiDivers($this->db);
								$contacts = $contact->getContactsOfSociety($tiersFromMulti->idTiers);
								if (is_array($contacts) && count($contacts) > 1) {

									// Retirer le primary_contact de la liste de contacts
									$tmpContacts = [];
									foreach ($contacts as $valueContact) {
										if ($valueContact->id !== $value["options_primary_contact"]) {
											$tmpContacts[] = $valueContact;
										}
									}
									$contacts = [];
									$contacts = $tmpContacts;

									foreach ($contacts as $keyc => $c) {
										foreach ($c as $keycbis => $valuecontactbis) {
											$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_contacts_' . ($keyc + 1) . '_' . $keycbis, $valuecontactbis);
										}
									}
								}
							}
						} else {
							$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, $value);
						}
					}
				}
			}
		}

		if ($affaire->array_options && $affaire->array_options['options_multitiers']) {
			$affaire->array_options['options_multitiers'] = json_decode($affaire->array_options['options_multitiers']);

			foreach ($affaire->array_options['options_multitiers'] as $tiersFromMulti) {
				// Récupération du tiers grace à son ID
				$avoDivers = new AvoloiDivers($this->db);
				$keys = $avoDivers->getSociety($tiersFromMulti->idTiers);
				$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_type', $tiersFromMulti->typeTiers);

				foreach ($keys as $key => $value) {
					if (preg_match('/logo$/', $key))	// Image
					{
						if (file_exists($value)) $templateProcessor->setImageValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, $value);
						else $templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, 'ErrorFileNotFound');
					} else {
						if (is_object($value) || is_array($value)) {
							if ($key === 'array_options') {
								foreach ($value as $key_array => $value_array) {
									$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key_array, $value_array);
								}

								// Pour variabiliser le contact principal
								if ($value["options_primary_contact"]) {
									// Get contact by ID
									$contact = $this->getContact($value["options_primary_contact"]);
									foreach ($contact as $keyContact => $valueContact) {
										// Set primary contact in tiers
										if (!is_object($valueContact) && !is_array($valueContact))
											$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_primary_contact_' . $keyContact, $valueContact);
									}
								}

								// Pour variabiliser les autres contacts
								$contact = new AvoloiDivers($this->db);
								$contacts = $contact->getContactsOfSociety($tiersFromMulti->idTiers);
								if (is_array($contacts) && count($contacts) > 1) {

									// Retirer le primary_contact de la liste de contacts
									$tmpContacts = [];
									foreach ($contacts as $valueContact) {
										if ($valueContact->id !== $value["options_primary_contact"]) {
											$tmpContacts[] = $valueContact;
										}
									}
									$contacts = [];
									$contacts = $tmpContacts;

									foreach ($contacts as $keyc => $c) {
										foreach ($c as $keycbis => $valuecontactbis) {
											$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_contacts_' . ($keyc + 1) . '_' . $keycbis, $valuecontactbis);
										}
									}
								}
							}
						} else {
							$templateProcessor->setValue($tiersFromMulti->typeTiers . $tiersFromMulti->posType . '_' . $key, $value);
						}
					}
				}
			}
		}

		// HOW TO :
		// Get the value
		// $companybic = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_BIC', 1);
		// if ($companybic) {
		// Set the value in the template
		// 	$templateProcessor->setValue('COMPANY_BIC', $companybic);
		// }
		if ($bankAccount) {
			$keys = get_object_vars($bankAccount);
			foreach ($keys as $key => $value) {
				if (!is_array($value) && !is_object($value)) {
					if (is_numeric($value)) {
						$value = number_format($value, 2);
					}
					$templateProcessor->setValue('BANK_' . $key, $value);
				}
			}
		}

		// print_r($object);

		if ($object->lines) {
			try {
				$templateProcessor->cloneRow('TABLE_description', count($object->lines));
				for ($i = 1; $i <= count($object->lines); $i++) {
					if ($object->lines[$i - 1]) {
						$keys = get_object_vars($object->lines[$i - 1]);
						foreach ($keys as $key => $value) {
							if (!is_array($value) && !is_object($value)) {
								if (is_numeric($value)) {
									$value = number_format($value, 2);
								}
								$templateProcessor->setValue('TABLE_' . $key . '#' . $i, $value);
							}
						}
					}
				}
			} catch (Exception $e) { }

			try {
				$templateProcessor->cloneBlock('COPYBLOC', count($object->lines));
				for ($i = 0; $i <= count($object->lines); $i++) {
					if ($object->lines[$i]) {
						$keys = get_object_vars($object->lines[$i]);
						foreach ($keys as $key => $value) {
							if (!is_array($value) && !is_object($value)) {
								if (is_numeric($value)) {
									$value = number_format($value, 2);
								}
								$templateProcessor->setValue($key, $value, 1);
							}
						}
					}
				}
			} catch (Exception $e) { }
		}

		// Replace labels translated
		$tmparray = $outputlangs->get_translations_for_substitutions();
		foreach ($tmparray as $key => $value) {
			try {
				$templateProcessor->setValue($key, $value);
			} catch (OdfException $e) {
				dol_syslog($e->getMessage(), LOG_INFO);
			}
		}

		// Set barreau
		$barreau = dolibarr_get_const($this->db, 'BARREAU_LABEL', 1);
		if ($barreau) {
			$templateProcessor->setValue('BARREAU_label', $barreau);
		}

		// Set society
		if ($tiers) {
			foreach ($tiers as $tkey => $tvalue) {
				if (!is_object($tvalue) && !is_array($tvalue)) {
					$templateProcessor->setValue('tiers_' . $tkey, $tvalue);
				}
			}

			if ($tiers->array_options) {

				// Pour variabiliser le contact principal
				if ($tiers->array_options["options_primary_contact"]) {
					// Get contact by ID
					$contact = $this->getContact($tiers->array_options["options_primary_contact"]);
					foreach ($contact as $keyContact => $valueContact) {
						// Set primary contact in tiers
						if (!is_object($valueContact) && !is_array($valueContact))
							$templateProcessor->setValue('tiers_primary_contact_' . $keyContact, $valueContact);
					}
				}

				// Pour variabiliser les autres contacts
				$contact = new AvoloiDivers($this->db);
				$contacts = $contact->getContactsOfSociety($tiers->id);
				if (is_array($contacts) && count($contacts) > 1) {

					// Retirer le primary_contact de la liste de contacts
					$tmpContacts = [];
					foreach ($contacts as $valueContact) {
						if ($valueContact->id !== $tiers->array_options["options_primary_contact"]) {
							$tmpContacts[] = $valueContact;
						}
					}
					$contacts = [];
					$contacts = $tmpContacts;

					foreach ($contacts as $keyc => $c) {
						foreach ($c as $keycbis => $valuecontactbis) {
							$templateProcessor->setValue('tiers_contacts_' . ($keyc + 1) . '_' . $keycbis, $valuecontactbis);
						}
					}
				}
			}
		}

		// Call the beforeODTSave hook
		$parameters = array('odfHandler' => &$templateProcessor, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
		$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

		// Write new file
		if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
			try {
				$templateProcessor->exportAsAttachedPDF($file);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}
		} else {
			try {
				$properties = $phpWord->getDocInfo();
				$properties->setCreator($user->getFullName($outputlangs));
				$properties->setTitle($object->builddoc_filename);
				$properties->setSubject($object->builddoc_filename);

				if (!empty($conf->global->ODT_ADD_DOLIBARR_ID)) {
					$templateProcessor->userdefined['dol_id'] = $object->id;
					$templateProcessor->userdefined['dol_element'] = $object->element;
				}

				switch ($typeDocument) {
					case 'invoice':
						if ($tiers) {
							$path = '/tiers/' . $tiers->nom . '/facture';
							if ($affaire) {
								$path = $path . '/' . $affaire->title;
							}
						} else if ($affaire) {
							$path = '/affaire/' . $affaire->title . '/facture';
						} else {
							$path = '/facture';
						}
						if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT . $path . '/facture'))) {
							mkdir($this->sanitizePath(DOL_DATA_ROOT . $path . '/facture'), 0700, true);
						}
						$storage = 'facture/' . $name . '_' . time() . '_' . $templateName;
						break;
					case 'acte':
						if ($tiers) {
							$path = '/tiers/' . $tiers->nom . '/acte';
							if ($affaire) {
								$path = $path . '/' . $affaire->title;
							}
						} else if ($affaire) {
							$path = '/affaire/' . $affaire->title . '/acte';
						} else {
							$path = '/acte';
						}
						if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT . $path . '/acte'))) {
							mkdir($this->sanitizePath(DOL_DATA_ROOT . $path . '/acte'), 0700, true);
						}
						$storage = 'affaire/' . $idType . '_' . time() . '_' . $templateName;
						break;
					case 'proposal':
						if ($tiers) {
							$path = '/tiers/' . $tiers->nom . '/propale';
							if ($affaire) {
								$path = $path . '/' . $affaire->title;
							}
						} else if ($affaire) {
							$path = '/affaire/' . $affaire->title . '/propale';
						} else {
							$path = '/propale';
						}
						if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT . $path . '/propale'))) {
							mkdir($this->sanitizePath(DOL_DATA_ROOT . $path . '/propale'), 0700, true);
						}
						$storage = 'propale/' . $name . '_' . time() . '_' . $templateName;
						break;
					case 'project':
						if ($tiers) {
							$path = '/tiers/' . $tiers->nom . '/affaire';
							if ($object) {
								$path = $path . '/' . $object->title;
							}
						} else if ($object) {
							$path = '/affaire/' . $object->title;
						} else {
							$path = '/affaire';
						}
						if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT . $path))) {
							mkdir($this->sanitizePath(DOL_DATA_ROOT . $path), 0700, true);
						}
						$storage = '/' . $object->title . '_' . time() . '_' . $templateName;
						break;
				}

				$newtmpfile = $templateProcessor->saveAs($this->sanitizePath(DOL_DATA_ROOT . $path . '/' . $storage));
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}
		}
		$parameters = array('odfHandler' => &$templateProcessor, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
		$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

		$templateProcessor = null;	// Destroy object
		$phpWord = null;	// Destroy object
		return array('code' => 1, 'documentPath' => $this->sanitizePath(DOL_DATA_ROOT . $path . '/' . $storage));

		$this->error = 'UnknownError';
		return -1;
	}

	/**
	 *	Function to create an affaire's docx
	 *
	 *	@param		int			$affaire_id			id of the affaire
	 * 	@param		string 		$template_path		template's path
	 *	@param		Translate	$output_langs		Lang output object
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function createDocxAffaire($affaire_id, $template_path, $output_langs) {

		global $user, $langs, $conf, $mysoc, $hookmanager, $action;

		
		if (! is_object($output_langs)) $output_langs=$langs;
		$sav_charset_output=$output_langs->charset_output;
		$output_langs->charset_output='UTF-8';
		
		// Load translation files required by the page
		@$output_langs->loadLangs(array("main", "dict", "companies", "projects"));
		
		// Add odtgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		$newfile=basename($template_path);

		// Get extension (ods or odt)
		$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
    	// Open and load template
		$phpWord = new \PhpOffice\PhpWord\PhpWord();

		try {
			$template = DOL_DOCUMENT_ROOT . $template_path;
			$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($template);
		}
		catch(Exception $e)
		{
			$this->error=$e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return -1;
		}

		$affaire = $this->getAffaire($affaire_id);

		if ($affaire->socid) {
			$affaire->tiers = $this->getSociety($affaire->socid);

			if ($affaire->tiers->array_options && $affaire->tiers->array_options['options_primary_contact']) {
				$affaire->tiers->primaryContact = $this->getContact($affaire->tiers->array_options['options_primary_contact']);
			}
		}

		$affaire->multitiers = array();

		if ($affaire->array_options) {
			if ($affaire->array_options['options_multitiers']) {
				$affaire->array_options['options_multitiers'] = json_decode($affaire->array_options['options_multitiers']);
	
				foreach($affaire->array_options['options_multitiers'] as $tiersFromArray) {
					$tiers = $this->getSociety($tiersFromArray->idTiers);
	
					if ($tiers->array_options && $tiers->array_options['options_primary_contact']) {
						$tiers->primaryContact = $this->getContact($tiers->array_options['options_primary_contact']);
					}
	
					array_push($affaire->multitiers, $tiers);
				}
			}

			if ($affaire->array_options['options_changelog']) {
				$affaire->notes = json_decode($affaire->array_options['options_changelog']);
			}
		} 
		
		$affaire->tasks = $this->getTasks($affaire_id);
		$affaire->invoices = $this->getInvoices($affaire_id);
		$affaire->documents = $this->getDocuments($affaire_id, $affaire->socid);
		$affaire->propals = $this->getPropals($affaire_id);

		// Make substitutions into odt
		$array_user=$this->get_substitutionarray_user($user, $output_langs);
		$array_soc=$this->get_substitutionarray_mysoc($mysoc, $output_langs);
		$array_thirdparty=$this->get_substitutionarray_thirdparty($affaire, $output_langs);
		$array_other=$this->get_substitutionarray_other($output_langs);
		$array_else=$this->getSubArrayAffaire($affaire, $output_langs);

		$tmp_array = array_merge($array_user, $array_soc, $array_thirdparty, $array_other, $array_else);

		$parameters=array('odfHandler'=>&$templateProcessor,'file'=>$file,'object'=>$affaire,'outputlangs'=>$output_langs,'substitutionarray'=>&$tmp_array);
		$reshook=$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		
		$this->setValues($templateProcessor, $tmp_array);

		// Call the beforeODTSave hook
		$parameters = array('odfHandler'=>&$templateProcessor,'file'=>$file,'object'=>$affaire,'outputlangs'=>$output_langs,'substitutionarray'=>&$tmp_array);
		$reshook=$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

		// Write new file
		try {
			$properties = $phpWord->getDocInfo();
			$properties->setCreator($user->getFullName($outputlangs));
			$properties->setTitle($affaire->builddoc_filename);
			$properties->setSubject($affaire->builddoc_filename);
	
			if (! empty($conf->global->ODT_ADD_DOLIBARR_ID))
			{
				$templateProcessor->userdefined['dol_id'] = $affaire->id;
				$templateProcessor->userdefined['dol_element'] = $affaire->element;
			}
	
			$path = '/exportedAffaires';

			if (!dol_is_dir($this->sanitizePath(DOL_DOCUMENT_ROOT.$path))) {
				mkdir($this->sanitizePath(DOL_DOCUMENT_ROOT.$path), 0700, true);
			}

			$filename = $affaire->title.'_at_'.time().'.docx';

			$newtmpfile = $templateProcessor->saveAs($this->sanitizePath(DOL_DOCUMENT_ROOT.$path.'/'.$filename));
	
		} catch (Exception $e){
			$this->error=$e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return -1;
		}
		

		$parameters = array('odfHandler'=>&$templateProcessor, 'file'=>$file, 'object'=>$affaire, 'outputlangs'=>$output_langs, 'substitutionarray'=>&$tmp_array);
		$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

		$templateProcessor = null;	// Destroy object
		$phpWord = null;	// Destroy object
		return array('code'=> 1, 'documentPath'=>$this->sanitizePath(DOL_DOCUMENT_ROOT . $path . '/' . $filename));
  
		$this->error='UnknownError';
		return -1;
	}

	private function setValues($templateProcessor, $values, $index = '') {
		foreach($values as $key=>$value) {
			$newKey = $key.$index;
			if (is_array($value) && substr($key, 0, 6) === 'block_') {
				$templateProcessor->cloneBlock($newKey, count($value), true, true);
				$i = 1;
				foreach($value as $key2=>$value2) {
					$this->setValues($templateProcessor, $value2, $index.'#'.$i);
					$i++;
				}
			} else {
				$templateProcessor->setValue($newKey, $value);
			}
		}
	}

	private function mapArr(&$item, $key) {

	}

	private function getSociety($id) {
		$societe = new Societe($this->db);
		$societe->fetch($id);
		return $societe;
	}

	private function getProposal($id)
	{
		$propal = new Propal($this->db);
		$propal->fetch($id);
		return $propal;
	}

	private function getPropals($affaire_id) {
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/api_proposals.class.php';

		$propalApi = new Proposals();

		try {
			$propals = $propalApi->index(null, null, null, null, '', "(t.fk_projet:=:'".$affaire_id."')");
		} catch (Exception $ex) {
			$propals = array();
		}

		return $propals;
	}

	private function getAffaire($id) {
		$affaire = new Project($this->db);
		$affaire->fetch($id);
		return $affaire;
	}

	private function getTasks($affaire_id) {
		require_once DOL_DOCUMENT_ROOT . '/projet/class/api_tasks.class.php';

		$taskApi = new Tasks();

		try {
			$tasks = $taskApi->index(null, null, null, null, "(t.fk_projet:=:'".$affaire_id."')");
		} catch (Exception $ex) {
			$tasks = array();
		}

		return $tasks;
	}

	private function getInvoice($id) {
		$invoice = new Facture($this->db);
		$invoice->fetch($id);
		return $invoice;
	}

	private function getInvoices($affaire_id) {

		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/api_invoices.class.php';

		$invoiceApi = new Invoices();
		try {
			$invoices = $invoiceApi->index(null, null, null, null, '', '', "(t.fk_projet:=:'".$affaire_id."')");
		} catch (Exception $ex) {
			$invoices = array();
		}

		return $invoices;
	}

	private function getDocuments($affaire_id, $tiers_id) {
		require_once DOL_DOCUMENT_ROOT . '/docxgenerator/class/api_docxgenerator.class.php';

		$documents = new Docxgenerator();

		try {
			$documents = $documents->getDocumentsListByElement('project', $affaire_id, $tiers_id);
		} catch (Exception $ex) {
			$documents = array();
		}

		return $documents;
	}

	private function getBankAccount($id) {
		$account = new Account($this->db);
		$account->fetch($id);
		return $account;
	}

	private function getContact($id)
	{
		$contact = new Contact($this->db);
		$contact->fetch($id);
		return $contact;
	}

	private function getSubArrayAffaire($affaire, $outputLang) {
		$paiementCondMode = array(
			"RECEP" => "A réception",
			"30D" => "30 jours",
			"30DENDMONTH" => "30 jours fin de mois",
			"60D" => "60 jours",
			"60DENDMONTH" => "60 jours fin de mois",
			"PT_ORDER" => "A commande",
			"PT_DELIVERY" => "A livraison",
			"PT_5050" => "50/50",
			"10D" => "10 jours",
			"10DENDMONTH" => "10 jours fin de mois",
			"14D" => "14 jours",
			"14DENDMONTH" => "14 jours fin de mois",
			"CB" => "Carte bancaire",
			"CHQ" => "Chèque",
			"LIQ" => "Espèce",
			"PRE" => "Ordre de prélèvement",
			"VIR" => "Virement bancaire"
		);


		$baseDatas = array(
			'affaire_titre'=>$affaire->title,
			'affaire_date_creation'=>dol_print_date($affaire->date_start, 'day', 'tzuser', $outputLang),
			'affaire_description'=>$affaire->description
		);

		$tiersDatas = array(
			'tiers_principal_nom'=>$affaire->tiers->name,
			'tiers_principal_ref_client'=>$affaire->tiers->code_client,
			'tiers_principal_tel_fixe'=>$affaire->tiers->phone,
			'tiers_principal_tel_mobile'=>$affaire->tiers->fax,
			'tiers_principal_email'=>$affaire->tiers->email,
			'tiers_principal_adresse'=>$affaire->tiers->address,
			'tiers_principal_code_postal'=>$affaire->tiers->zip,
			'tiers_principal_ville'=>$affaire->tiers->town			
		);

		$tiersContactDatas = array(
			'contact_principal_nom'=>$affaire->tiers->primaryContact->lastname,
			'contact_principal_prenom'=>$affaire->tiers->primaryContact->firstname,
			'contact_principal_tel_fixe'=>$affaire->tiers->primaryContact->phone_perso,
			'contact_principal_tel_mobile'=>$affaire->tiers->primaryContact->phone_mobile,
			'contact_principal_email'=>$affaire->tiers->primaryContact->email,
			'contact_principal_adresse'=>$affaire->tiers->primaryContact->address,
			'contact_principal_code_postal'=>$affaire->tiers->primaryContact->zip,
			'contact_principal_ville'=>$affaire->tiers->primaryContact->town
		);

		$multitiersDatas = array();
		foreach($affaire->multitiers as $index=>$multitiers) {
			array_push($multitiersDatas, array(
				'multitiers_nom'=>$multitiers->name,
				'multitiers_ref_client'=>$multitiers->code_client,
				'multitiers_tel_fixe'=>$multitiers->phone,
				'multitiers_tel_mobile'=>$multitiers->fax,
				'multitiers_email'=>$multitiers->email,
				'multitiers_addresse'=>$multitiers->address,
				'multitiers_code_postal'=>$multitiers->zip,
				'multitiers_ville'=>$multitiers->town,
				'multitiers_contact_principal_nom'=>$multitiers->primaryContact->lastName,
				'multitiers_contact_principal_prenom'=>$multitiers->primaryContact->firstName,
				'multitiers_contact_principal_tel_fixe'=>$multitiers->primaryContact->phone_perso,
				'multitiers_contact_principal_tel_mobile'=>$multitiers->primaryContact->phone_mobile,
				'multitiers_contact_principal_email'=>$multitiers->primaryContact->email,
				'multitiers_contact_principal_adresse'=>$multitiers->primaryContact->address,
				'multitiers_contact_principal_code_postal'=>$multitiers->primaryContact->zip,
				'multitiers_contact_principal_ville'=>$multitiers->primaryContact->town,
			));
		}

		$notesDatas = array();
		foreach($affaire->notes as $index=>$note) {
			array_push($notesDatas, array(
				'note_nom_utilisateur'=>$note->username,
				'note_contenu'=>$note->content,
				'note_date'=>dol_print_date($note->date, 'dayhour', 'tzuser', $outputLang),
			));
		}

		$tasksDatas = array();
		foreach($affaire->tasks as $index=>$task) {
			array_push($tasksDatas, array(
				'task_description'=>$task->label,
				'task_date'=>dol_print_date($task->date_start, 'day', 'tzuser', $outputLang),
				'task_duree'=>number_format(($task->planned_workload / 3600), 2, ',', ' '),
				'task_type_act'=>$task->array_options['options_type_rdv']
			));
		}
		
		$documentsDatas = array();
		foreach($affaire->documents as $index=>$document) {
			array_push($documentsDatas, array(
				'document_titre'=>$document->name
			));
		}

		$invoicesDatas = array();
		foreach($affaire->invoices as $index=>$invoice) {
			$factLineDatas = array();

			foreach($invoice->lines as $index2=>$line) {
				array_push($factLineDatas, array(
					'linefact_description'=>$line->description,
					'linefact_tarif_horaire'=>number_format($line->multicurrency_subprice, 1, ',', ' '),
					'linefact_taux_tva'=>number_format($line->tva_tx, 0, ',', ' '),
					'linefact_nb_heures'=>number_format($line->qty, 0, ',', ' '),
					'linefact_remise'=>number_format($line->remise_percent, 0, ',', ' '),
					'linefact_total_ht'=>number_format($line->total_ht, 3, ',', ' '),
					'linefact_total_ttc'=>number_format($line->total_ttc, 3, ',', ' '),
				));
			}

			array_push($invoicesDatas, array(
				'invoice_ref'=>$invoice->ref,
				'invoice_date'=>dol_print_date($invoice->date, 'day', 'tzuser', $outputLang),
				'invoice_libelle'=>$invoice->array_options['options_titre'],
				'invoice_mode_paiement'=>$paiementCondMode[$invoice->mode_reglement_code],
				'invoice_cond_paiement'=>$paiementCondMode[$invoice->cond_reglement_code],
				'block_invoice_lines'=>$factLineDatas
			));			
		}

		$propalDatas = array();
		foreach($affaire->propals as $index=>$propal) {
			$propalLineDatas = array();

			foreach($propal->lines as $index2=>$line) {
				array_push($propalLineDatas, array(
					'linefact_description'=>$line->description,
					'linefact_tarif_horaire'=>number_format($line->multicurrency_subprice, 1, ',', ' '),
					'linefact_taux_tva'=>number_format($line->tva_tx, 0, ',', ' '),
					'linefact_nb_heures'=>number_format($line->qty, 0, ',', ' '),
					'linefact_remise'=>number_format($line->remise_percent, 0, ',', ' '),
					'linefact_total_ht'=>number_format($line->total_ht, 3, ',', ' '),
					'linefact_total_ttc'=>number_format($line->total_ttc, 3, ',', ' '),
				));
			}

			array_push($propalDatas, array(
				'propal_titre'=>$propal->array_options['options_titre'],
				'propal_date'=>dol_print_date($propal->date, 'day', 'tzuser', $outputLang),
				'propal_duree_valid'=>number_format(($propal->fin_validite - $propal->date) / 86400, 0, ',', ' '),
				'propal_mode_paiement'=>$paiementCondMode[$propal->mode_reglement_code],
				'propal_cond_paiement'=>$paiementCondMode[$propal->cond_reglement_code],
				'block_propals_lines'=>$propalLineDatas
			));
		}

		$listDatas = array(
			'block_multitiers'=>$multitiersDatas,
			'block_notes'=>$notesDatas,
			'block_tasks'=>$tasksDatas,
			'block_documents'=>$documentsDatas,
			'block_invoices'=>$invoicesDatas,
			'block_propals'=>$propalDatas
		);

		return array_merge(
			$baseDatas, 
			$tiersDatas, 
			$tiersContactDatas,
			$listDatas
		);
	}

	public function sanitizePath($str)
	{
		$unwanted_array = array(
			'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
			'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', '/\s+/' => '_', ' ' => '_'
		);
		return strtr($str, $unwanted_array);
	}
}

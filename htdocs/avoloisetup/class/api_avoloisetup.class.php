<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 */

use Luracast\Restler\RestException;
use Luracast\Restler\Format\UploadFormat;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiSetup {@requires user,external}
 */
class AvoloiSetup extends DolibarrApi
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
        global $db, $conf;
        $this->db = $db;
    }

    // public functio

    /**
     * Get properties of an avoloi_setup object
     *
     * Return an array with avoloi_setup informations
     *
     * @return 	array|mixed data without useless information
     * 
     * @throws 	RestException
     *
     * @url	GET /
     */
    public function get()
    {
        dol_include_once('/core/lib/admin.lib.php');

        // STEP 1
        $companysociete_type = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TYPE', 1);
        $companyformejuridique = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', 1);
        $companyname = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM', 1);
        $companyname_lettres = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM_LETTRES', 1);
        $companyname_actes = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM_ACTES', 1);
        $companycode_contact = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_CODE_CONTACT', 1);
        $companyprofession = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_PROFESSION', 1);
        $companytype_profil = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TYPE_PROFIL', 1);
        $companytype_barreau = dolibarr_get_const($this->db, 'BARREAU_LABEL', 1);
        $companylogo_filename = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_LOGO', 1);

        // STEP 2
        $companyemail = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_MAIL', 1);
        $companyphone_fix = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TEL_FIX', 1);
        $companyphone_mobile = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TEL_MOBILE', 1);
        $companyaddress = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ADDRESS', 1);
        $companyzip = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ZIP', 1);
        $companytown = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TOWN', 1);
        $companycountry_coordonnees = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_COUNTRY_COORDONNEES', 1);

        // STEP 3
        $companyid_country_banque = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ID_COUNTRY_BANQUE', 1);
        $companyiban = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_IBAN', 1);
        $companybic = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_BIC', 1);

        // STEP 4
        $companyid_country_commerce = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ID_COUNTRY_COMMERCE', 1);
        $companyvta_number = dolibarr_get_const($this->db, 'MAIN_INFO_TVAINTRA', 1);
        $companyvta_liable = dolibarr_get_const($this->db, 'FACTURE_TVAOPTION', 1);
        $companycode_rcs = dolibarr_get_const($this->db, 'MAIN_INFO_CODE_RCS', 1);
        $companyville_rcs = dolibarr_get_const($this->db, 'MAIN_INFO_VILLE_RCS', 1);
        $companytva_intracommunautaire = dolibarr_get_const($this->db, 'MAIN_INFO_TVA_INTRACOMMUNAUTAIRE', 1);
        $companysiren = dolibarr_get_const($this->db, 'MAIN_INFO_SIREN', 1);
        $companysiret = dolibarr_get_const($this->db, 'MAIN_INFO_SIRET', 1);
        $companyape = dolibarr_get_const($this->db, 'MAIN_INFO_APE', 1);
        $companyident_reg_commerce = dolibarr_get_const($this->db, 'MAIN_INFO_REG_COMMERCE', 1);
        $companycapital = dolibarr_get_const($this->db, 'MAIN_INFO_CAPITAL', 1);
        $companycnbe = dolibarr_get_const($this->db, 'MAIN_INFO_CNBE', 1);
        $companycarpa = dolibarr_get_const($this->db, 'MAIN_INFO_CARPA', 1);


        $list = array();

        // STEP 1
        $list['societe_type']=$companysociete_type;
        $list['formejuridique']=$companyformejuridique;
        $list['name']=$companyname;
        $list['name_lettres']=$companyname_lettres;
        $list['name_actes']=$companyname_actes;
        $list['code_contact']=$companycode_contact;
        $list['profession']=$companyprofession;
        $list['type_profil']=$companytype_profil;
        $list['barreau']=$companytype_barreau;
        $list['logo_filename']=$companylogo_filename;

        // STEP 2
        $list['email']=$companyemail;
        $list['phone_fix']=$companyphone_fix;
        $list['phone_mobile']=$companyphone_mobile;
        $list['address']=$companyaddress;
        $list['zip']=$companyzip;
        $list['town']=$companytown;
        $list['country_coordonnees']=$companycountry_coordonnees;

        // STEP 3
        $list['id_country_banque']=$companyid_country_banque;
        $list['iban']=$companyiban;
        $list['bic']=$companybic;

        // STEP 4
        $list['id_country_commerce']=$companyid_country_commerce;
        $list['vta_number']=$companyvta_number;
        $list['vta_liable']=$companyvta_liable;
        $list['code_rcs']=$companycode_rcs;
        $list['ville_rcs']=$companyville_rcs;
        $list['tva_intracommunautaire']=$companytva_intracommunautaire;
        $list['siren']=$companysiren;
        $list['siret']=$companysiret;
        $list['ape']=$companyape;
        $list['ident_reg_commerce']=$companyident_reg_commerce;
        $list['capital']=$companycapital;
        $list['cnbe']=$companycnbe;
        $list['carpa']=$companycarpa;

        return $list;
    }

    /**
     * Get the list of departements.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @return array                List of departements
     *
     * @url     GET /departements
     *
     * @throws RestException
     */
    public function getDepartements($sortfield = "code_departement", $sortorder = 'ASC')
    {
        $list = array();

        $sql = "SELECT rowid, code_departement, nom FROM ".MAIN_DB_PREFIX."c_departements as t";
        $sql .= " WHERE t.rowid > 1 AND t.rowid < 103";

        $sql.= $this->db->order($sortfield, $sortorder);

        $result = $this->db->query($sql);

		header('HTTP/1.1 501 API not found (failed to include API file)');

        return $result;
    }

    /**
     * Get the list of departements.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @return array                List of departements
     *
     * @url     GET /formesjuridiques
     *
     * @throws RestException
     */
    public function getFormesJuridiques($sortfield = "", $sortorder = 'ASC')
    {
        $list = array();

        $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_forme_juridique as t";
        $sql .= " WHERE t.fk_pays = '1'";

        $sql.= $this->db->order($sortfield, $sortorder);

        $result = $this->db->query($sql);

        return $result;
    }

    /**
     * Set properties of an avoloi_setup object
     *
     * Return an array with avoloi_setup informations
     *
     * @param   array   $setup_infos
     * @return 	array|mixed data without useless information
     *
     * @url	PUT /
     * @throws 	RestException
     */
    public function set($setup_infos)
    {
        dol_include_once('/core/lib/admin.lib.php');

        $setup_infos = (object) $setup_infos;

        // STEP 1
        if ($setup_infos->societe_type) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TYPE', $setup_infos->societe_type);
        if ($setup_infos->formejuridique) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', $setup_infos->formejuridique);
        if ($setup_infos->name) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOM', $setup_infos->name);
        if ($setup_infos->name_lettres) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOM_LETTRES', $setup_infos->name_lettres);
        if ($setup_infos->name_actes) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOM_ACTES', $setup_infos->name_actes);
        if ($setup_infos->code_contact) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_CODE_CONTACT', $setup_infos->code_contact);
        if ($setup_infos->profession) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_PROFESSION', $setup_infos->profession);
        if ($setup_infos->type_profil) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TYPE_PROFIL', $setup_infos->type_profil);
        if ($setup_infos->barreau) dolibarr_set_const($this->db, 'BARREAU_LABEL', $setup_infos->barreau);
        if ($setup_infos->logo_filename) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_LOGO', $setup_infos->logo_filename);

        // STEP 2
        if ($setup_infos->email) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_MAIL', $setup_infos->email);
        if ($setup_infos->phone_fix) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TEL_FIX', $setup_infos->phone_fix);
        if ($setup_infos->phone_mobile) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TEL_MOBILE', $setup_infos->phone_mobile);
        if ($setup_infos->address) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ADDRESS', $setup_infos->address);
        if ($setup_infos->zip) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ZIP', $setup_infos->zip);
        if ($setup_infos->town) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TOWN', $setup_infos->town);
        if ($setup_infos->country_coordonnees) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_COUNTRY_COORDONNEES', $setup_infos->country_coordonnees);

        // STEP 3
        if ($setup_infos->id_country_banque) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ID_COUNTRY_BANQUE', $setup_infos->id_country_banque);
        if ($setup_infos->iban) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_IBAN', $setup_infos->iban);
        if ($setup_infos->bic) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_BIC', $setup_infos->bic);

        // STEP 4
        dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ID_COUNTRY_COMMERCE', $setup_infos->id_country_commerce);
        dolibarr_set_const($this->db, 'MAIN_INFO_TVAINTRA', $setup_infos->vta_number);
        dolibarr_set_const($this->db, 'FACTURE_TVAOPTION', $setup_infos->vta_liable ? $setup_infos->vta_liable : '0');
        dolibarr_set_const($this->db, 'MAIN_INFO_CODE_RCS', $setup_infos->code_rcs ? $setup_infos->code_rcs : null);
        dolibarr_set_const($this->db, 'MAIN_INFO_VILLE_RCS', $setup_infos->ville_rcs ? $setup_infos->ville_rcs : null);
        dolibarr_set_const($this->db, 'MAIN_INFO_TVA_INTRACOMMUNAUTAIRE', $setup_infos->tva_intracommunautaire ? $setup_infos->tva_intracommunautaire : null);
        if ($setup_infos->siren) dolibarr_set_const($this->db, 'MAIN_INFO_SIREN', $setup_infos->siren);
        if ($setup_infos->siret) dolibarr_set_const($this->db, 'MAIN_INFO_SIRET', $setup_infos->siret);
        if ($setup_infos->ape) dolibarr_set_const($this->db, 'MAIN_INFO_APE', $setup_infos->ape);
        if ($setup_infos->ident_reg_commerce) dolibarr_set_const($this->db, 'MAIN_INFO_REG_COMMERCE', $setup_infos->ident_reg_commerce);
        dolibarr_set_const($this->db, 'MAIN_INFO_CAPITAL', $setup_infos->capital ? $setup_infos->capital : null);
        dolibarr_set_const($this->db, 'MAIN_INFO_CNBE', $setup_infos->cnbe ? $setup_infos->cnbe : null);
        dolibarr_set_const($this->db, 'MAIN_INFO_CARPA', $setup_infos->carpa ? $setup_infos->carpa : null);

        

        // if ($setup_infos->stateid) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_STATE', $setup_infos->stateid);
        // if ($setup_infos->fax) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_FAX', $setup_infos->fax);
        // if ($setup_infos->webaddress) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_WEB', $setup_infos->webaddress);
        // if ($setup_infos->note) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOTE', $setup_infos->note);
        // if ($setup_infos->ceo) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_MANAGERS', $setup_infos->ceo);
        // if ($setup_infos->dpo) dolibarr_set_const($this->db, 'MAIN_INFO_GDPR', $setup_infos->dpo);
        // if ($setup_infos->object) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_OBJECT', $setup_infos->object);
        // if ($setup_infos->fiscal_month_start) dolibarr_set_const($this->db, 'SOCIETE_FISCAL_MONTH_START', $setup_infos->fiscal_month_start);

        return $this->get();
    }

    // /**
    //  * Get the list of departements.
    //  *
    //  * @param string    $sortfield  Sort field
    //  * @param string    $sortorder  Sort order
    //  * @return array                List of departements
    //  *
    //  * @url     GET /formesjuridiques
    //  *
    //  * @throws RestException
    //  */
    // public function getFormesJuridiques($sortfield = "", $sortorder = 'ASC')
    // {
    //     $list = array();

    //     $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_forme_juridique as t";
    //     $sql .= " WHERE t.fk_pays = '1'";

    //     $sql.= $this->db->order($sortfield, $sortorder);

    //     $result = $this->db->query($sql);

    //     return $result;
    // }

    /**
     * Set properties of an accounting_setup object
     *
     * Return an array with accounting_setup informations
     *
     * @param   array   $setup_infos
     * @return 	array|mixed data without useless information
     *
     * @url	PUT /accounting-setup
     * @throws 	RestException
     */
    public function setAccountingSetup($setup_infos)
    {
        dol_include_once('/core/lib/admin.lib.php');

        $setup_infos = (object) $setup_infos;

        dolibarr_set_const($this->db, 'FACTURE_ADDON', 'mod_facture_mercure');

        if ($setup_infos->invoice_nomenclature || $setup_infos->invoice_nomenclature == '') dolibarr_set_const($this->db, 'FACTURE_MERCURE_MASK_INVOICE', $setup_infos->invoice_nomenclature);
        if ($setup_infos->credit_nomenclature || $setup_infos->credit_nomenclature == '') dolibarr_set_const($this->db, 'FACTURE_MERCURE_MASK_CREDIT', $setup_infos->credit_nomenclature);
        if ($setup_infos->tiers_nomenclature || $setup_infos->tiers_nomenclature == '') dolibarr_set_const($this->db, 'COMPANY_ELEPHANT_MASK_CUSTOMER', $setup_infos->tiers_nomenclature);
        if ($setup_infos->client_time_limit) dolibarr_set_const($this->db, 'CLIENT_TIME_LIMIT', $setup_infos->client_time_limit);
        if ($setup_infos->provider_time_limit) dolibarr_set_const($this->db, 'PROVIDER_TIME_LIMIT', $setup_infos->provider_time_limit);
        if ($setup_infos->taux_tva_default || $setup_infos->taux_tva_default == '0') dolibarr_set_const($this->db, 'TAUX_TVA_DEFAULT', $setup_infos->taux_tva_default);

        return $this->getAccountingSetup();
    }

    /**
     * Get properties of an accounting_setup object
     *
     * Return an array with accounting_setup informations
     *
     * @return 	array|mixed data without useless information
     *
     * @url	GET /accounting-setup
     * @throws 	RestException
     */
    public function getAccountingSetup()
    {
        dol_include_once('/core/lib/admin.lib.php');

        $invoice_nomenclature = dolibarr_get_const($this->db, 'FACTURE_MERCURE_MASK_INVOICE', 1);
        $credit_nomenclature = dolibarr_get_const($this->db, 'FACTURE_MERCURE_MASK_CREDIT', 1);
        $tiers_nomenclature = dolibarr_get_const($this->db, 'COMPANY_ELEPHANT_MASK_CUSTOMER', 1);
        $client_time_limit = dolibarr_get_const($this->db, 'CLIENT_TIME_LIMIT', 1);
        $provider_time_limit = dolibarr_get_const($this->db, 'PROVIDER_TIME_LIMIT', 1);
        $taux_tva_default = dolibarr_get_const($this->db, 'TAUX_TVA_DEFAULT', 1);

        $list = array();

        $list['invoice_nomenclature']=$invoice_nomenclature;
        $list['credit_nomenclature']=$credit_nomenclature;
        $list['tiers_nomenclature']=$tiers_nomenclature;
        $list['client_time_limit']=$client_time_limit;
        $list['provider_time_limit']=$provider_time_limit;
        $list['taux_tva_default']=$taux_tva_default;

        return $list;
    }

    /**
	 * Return the list of tva of an array of country
	 * 
	 * @param   string   $country_code
	 * @return  array                   List of tva
	 *
	 * @throws 200
	 *
	 * @url GET /tauxtva
	 */
	public function getTauxTva($country_id)
	{
		global $langs;
		$const_code = 999;

		$sql  = "SELECT DISTINCT t.rowid, t.code, t.taux, t.localtax1, t.localtax1_type, t.localtax2, t.localtax2_type, t.recuperableonly, t.note";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_tva as t";
		$sql .= " LEFT JOIN ". MAIN_DB_PREFIX . "c_country as c";
		$sql .= " ON t.fk_pays = c.rowid";
		$sql .= " WHERE t.active > 0";
		$sql .= " AND t.fk_pays IN (" . $country_id . ',' . $const_code . ")";
		$sql .= " ORDER BY t.code ASC, t.taux ASC, t.recuperableonly ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				for ($i = 0; $i < $num; $i++) {
					$obj = $this->db->fetch_object($resql);
					$this->cache_vatrates[$i]['rowid']	= $obj->rowid;
					$this->cache_vatrates[$i]['code']	= $obj->code;
					$this->cache_vatrates[$i]['txtva']	= $obj->taux;
					$this->cache_vatrates[$i]['nprtva']	= $obj->recuperableonly;
					$this->cache_vatrates[$i]['localtax1']	    = $obj->localtax1;
					$this->cache_vatrates[$i]['localtax1_type']	= $obj->localtax1_type;
					$this->cache_vatrates[$i]['localtax2']	    = $obj->localtax2;
					$this->cache_vatrates[$i]['localtax2_type']	= $obj->localtax1_type;
                    $this->cache_vatrates[$i]['note'] = $obj->note;
					$this->cache_vatrates[$i]['label']	= $obj->taux . '%' . ($obj->code ? ' (' . $obj->code . ')' : '');   // Label must contains only 0-9 , . % or *
					$this->cache_vatrates[$i]['labelallrates'] = $obj->taux . '/' . ($obj->localtax1 ? $obj->localtax1 : '0') . '/' . ($obj->localtax2 ? $obj->localtax2 : '0') . ($obj->code ? ' (' . $obj->code . ')' : '');	// Must never be used as key, only label
					$positiverates = '';
					if ($obj->taux) $positiverates .= ($positiverates ? '/' : '') . $obj->taux;
					if ($obj->localtax1) $positiverates .= ($positiverates ? '/' : '') . $obj->localtax1;
					if ($obj->localtax2) $positiverates .= ($positiverates ? '/' : '') . $obj->localtax2;
					if (empty($positiverates)) $positiverates = '0';
					$this->cache_vatrates[$i]['labelpositiverates'] = $positiverates . ($obj->code ? ' (' . $obj->code . ')' : '');	// Must never be used as key, only label
				}

				return $this->cache_vatrates;
			} else {
				$this->error = '<font class="error">' . $langs->trans("ErrorNoVATRateDefinedForSellerCountry", $country_id) . '</font>';
				return -1;
			}
		} else {
			$this->error = '<font class="error">' . $this->db->error() . '</font>';
			return -2;
		}
	}

	/**
	 * create a new tva
	 * 
	 * @param   string   $libelle
     * @param   string   $taux
     * 
	 * @return  array    List of tva
	 *
	 * @throws 200
     * @throws 400
     * @throws 500
	 *
	 * @url POST /createtva
	 */
	public function addTauxTva($libelle, $taux)
	{
		global $langs;
		$const_code = 999;
		// echo 'test';

		if (!$taux || $taux === '') {
			throw new RestException(400, 'Le taux est manquant');
		}

		if (!$libelle || $libelle === '') {
			throw new RestException(400, 'Le libelle est manquant');
		}

		if ($taux >= 0) {
			$sql  = "INSERT INTO `" . MAIN_DB_PREFIX . "c_tva` (`fk_pays`, `code`, `taux`, `localtax1`, `localtax1_type`, `localtax2`, `localtax2_type`, `recuperableonly`, `note`, `active`, `accountancy_code_sell`, `accountancy_code_buy`)";
			$sql .= "VALUES (" . $const_code . ", '', " . $taux . ", '0', '0', '0', '0', 0, '" . $libelle . "', 1, NULL, NULL)";
			// echo $sql;
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "c_tva");
				return $this->id;
			} else {
				$error = $this->db->lasterror();
				$this->db->rollback();
				throw new RestException(500, $error);
			}
		} else {
			throw new RestException(400, 'Le taux ne peut pas être infèrieur à 0');
		}
	}


    /**
     * Get properties of an accounting_setup object
     *
     * Return an array with accounting_setup informations
     *
     * @return 	array|mixed data without useless information
     *
     * @url	GET /canCreateInvoices
     * @throws 	RestException
     */
    public function canCreateInvoices() {
        // Récupération des différentes valeurs à vérifier
        $companysociete_type = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TYPE', 1);
        $companyformejuridique = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', 1);
        $companyname = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM', 1);
        $companyname_lettres = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM_LETTRES', 1);
        $companyname_actes = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM_ACTES', 1);
        $companycode_contact = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_CODE_CONTACT', 1);
        $companyprofession = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_PROFESSION', 1);
        // $companytype_profil = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TYPE_PROFIL', 1);
        $companyemail = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_MAIL', 1);
        $companyphone_fix = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TEL_FIX', 1);
        $companyphone_mobile = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TEL_MOBILE', 1);
        $companyaddress = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ADDRESS', 1);
        $companyzip = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ZIP', 1);
        $companytown = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_TOWN', 1);
        $companycountry_coordonnees = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_COUNTRY_COORDONNEES', 1);
        // $companyid_country_commerce = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_ID_COUNTRY_COMMERCE', 1);
        // $companyvta_liable = dolibarr_get_const($this->db, 'FACTURE_TVAOPTION', 1);
        // $companycode_rcs = dolibarr_get_const($this->db, 'MAIN_INFO_CODE_RCS', 1);
        // $companyville_rcs = dolibarr_get_const($this->db, 'MAIN_INFO_VILLE_RCS', 1);
        // $companytva_intracommunautaire = dolibarr_get_const($this->db, 'MAIN_INFO_TVA_INTRACOMMUNAUTAIRE', 1);
        $companysiren = dolibarr_get_const($this->db, 'MAIN_INFO_SIREN', 1);
        $companysiret = dolibarr_get_const($this->db, 'MAIN_INFO_SIRET', 1);
        $companyape = dolibarr_get_const($this->db, 'MAIN_INFO_APE', 1);
        $companyident_reg_commerce = dolibarr_get_const($this->db, 'MAIN_INFO_REG_COMMERCE', 1);
        // $companycapital = dolibarr_get_const($this->db, 'MAIN_INFO_CAPITAL', 1);
        // $companycnbe = dolibarr_get_const($this->db, 'MAIN_INFO_CNBE', 1);
        // $companycarpa = dolibarr_get_const($this->db, 'MAIN_INFO_CARPA', 1);

        // Vérification de l'existence d'un compte bancaire
		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as b";

        $result = $this->db->query($sql);

        if (!$result->num_rows || $result->num_rows <= 0
            || (!$companysociete_type || $companysociete_type === '')
            || (!$companyformejuridique || $companyformejuridique === '')
            || (!$companyname || $companyname === '')
            || (!$companyname_lettres || $companyname_lettres === '')
            || (!$companyname_actes || $companyname_actes === '')
            || (!$companycode_contact || $companycode_contact === '')
            || (!$companyprofession || $companyprofession === '')
            || (!$companyemail || $companyemail === '')
            || (!$companyphone_fix || $companyphone_fix === '')
            || (!$companyphone_mobile || $companyphone_mobile === '')
            || (!$companyaddress || $companyaddress === '')
            || (!$companyzip || $companyzip === '')
            || (!$companytown || $companytown === '')
            || (!$companycountry_coordonnees || $companycountry_coordonnees === '')
            // || (!$companyid_country_commerce || $companyid_country_commerce === '')
            // || (!$companyvta_liable || $companyvta_liable === '')
            // || (!$companycode_rcs || $companycode_rcs === '')
            // || (!$companyville_rcs || $companyville_rcs === '')
            // || (!$companytva_intracommunautaire || $companytva_intracommunautaire === '')
            || (!$companysiren || $companysiren === '')
            || (!$companysiret || $companysiret === '')
            || (!$companyape || $companyape === '')
            || (!$companyident_reg_commerce || $companyident_reg_commerce === '')
            // || (!$companycapital || $companycapital === '')
            // || (!$companycnbe || $companycnbe === '')
            // || (!$companycarpa || $companycarpa === '')
            ) {
                return false;
        } else {
            return true;
        }
    }
}

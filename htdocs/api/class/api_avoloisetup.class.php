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
// require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';



/**
 * \file    htdocs/modulebuilder/template/class/api_mymodule.class.php
 * \ingroup mymodule
 * \brief   File for API management of myobject.
 */

/**
 * API class for mymodule myobject
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Avoloisetup extends DolibarrApi
{
    /**
     * @var AvoloiSetup $myobject {@type MyObject}
     */
    public $avoloi_setup;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
    }

    /**
     * Get properties of a avoloi_setup object
     *
     * Return an array with avoloi_setup informations
     *
     * @return 	array|mixed data without useless information
     *
     * @url	GET /
     * @throws 	RestException
     */
    public function get()
    {
        dol_include_once('/core/lib/admin.lib.php');

        // Step 1
        $companyname = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_NOM", 1);
        $companyaddress = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS", 1);
        $companyzip = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_ZIP", 1);
        $companytown = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_TOWN", 1);
        $companystateid = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_STATE", 1);
        $companyphone = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_TEL", 1);
        $companyfax = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_FAX", 1);
        $companyemail = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_MAIL", 1);
        $companywebsiteaddress = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_WEB", 1);
        $companynote = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_NOTE", 1);

        // Step 2
        $companylogo = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_LOGO", 1);

        // Step 3
        $companyceo = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_MANAGERS", 1);
        $companydpo = dolibarr_get_const($this->db, "MAIN_INFO_GDPR", 1);
        $companycapital = dolibarr_get_const($this->db, "MAIN_INFO_CAPITAL", 1);
        $companyformejuridiqueid = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_FORME_JURIDIQUE", 1);
        $companysiren = dolibarr_get_const($this->db, "MAIN_INFO_SIREN", 1);
        $companysiret = dolibarr_get_const($this->db, "MAIN_INFO_SIRET", 1);
        $companyape = dolibarr_get_const($this->db, "MAIN_INFO_APE", 1);
        $companyrcs = dolibarr_get_const($this->db, "MAIN_INFO_RCS", 1);
        $companycnbe = dolibarr_get_const($this->db, "MAIN_INFO_CNBE", 1);
        $companycarpa = dolibarr_get_const($this->db, "MAIN_INFO_CARPA", 1);
        $companyobject = dolibarr_get_const($this->db, "MAIN_INFO_SOCIETE_OBJECT", 1);

        // Step 4
        $companyfiscalmonthstart = dolibarr_get_const($this->db, "SOCIETE_FISCAL_MONTH_START", 1);
        $companytvaintra = dolibarr_get_const($this->db, "MAIN_INFO_TVAINTRA", 1);
        $companyisvtaliable = dolibarr_get_const($this->db, "FACTURE_TVAOPTION", 1);

        $list = array();

        // Step 1
        $list['name']=$companyname;
        $list['address']=$companyaddress;
        $list['zip']=$companyzip;
        $list['town']=$companytown;
        $list['stateid']=$companystateid;
        $list['phone']=$companyphone;
        $list['fax']=$companyfax;
        $list['email']=$companyemail;
        $list['webaddress']=$companywebsiteaddress;
        $list['note']=$companynote;

        // Step 2
        $list['logo_filename']=$companylogo;

        // Step 3
        $list['ceo']=$companyceo;
        $list['dpo']=$companydpo;
        $list['capital']=$companycapital;
        $list['formejuridique']=$companyformejuridiqueid;
        $list['siren']=$companysiren;
        $list['siret']=$companysiret;
        $list['ape']=$companyape;
        $list['rcs']=$companyrcs;
        $list['cnbe']=$companycnbe;
        $list['carpa']=$companycarpa;
        $list['object']=$companyobject;

        // Step 4
        $list['fiscal_month_start']=$companyfiscalmonthstart;
        $list['vta_number']=$companytvaintra;
        $list['vta_liable']=$companyisvtaliable;

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
     * Set properties of a avoloi_setup object
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

        // Step 1
        if ($setup_infos->name) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOM', $setup_infos->name);
        if ($setup_infos->address) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ADDRESS', $setup_infos->address);
        if ($setup_infos->zip) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_ZIP', $setup_infos->zip);
        if ($setup_infos->town) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TOWN', $setup_infos->town);
        if ($setup_infos->stateid) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_STATE', $setup_infos->stateid);
        if ($setup_infos->phone) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_TEL', $setup_infos->phone);
        if ($setup_infos->fax) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_FAX', $setup_infos->fax);
        if ($setup_infos->email) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_MAIL', $setup_infos->email);
        if ($setup_infos->webaddress) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_WEB', $setup_infos->webaddress);
        if ($setup_infos->note) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_NOTE', $setup_infos->note);

        // Step 2
        if ($setup_infos->logo_filename) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_LOGO', $setup_infos->logo_filename);

        // Step 3
        if ($setup_infos->ceo) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_MANAGERS', $setup_infos->ceo);
        if ($setup_infos->dpo) dolibarr_set_const($this->db, 'MAIN_INFO_GDPR', $setup_infos->dpo);
        if ($setup_infos->capital) dolibarr_set_const($this->db, 'MAIN_INFO_CAPITAL', $setup_infos->capital);
        if ($setup_infos->formejuridique) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', $setup_infos->formejuridique);
        if ($setup_infos->siren) dolibarr_set_const($this->db, 'MAIN_INFO_SIREN', $setup_infos->siren);
        if ($setup_infos->siret) dolibarr_set_const($this->db, 'MAIN_INFO_SIRET', $setup_infos->siret);
        if ($setup_infos->ape) dolibarr_set_const($this->db, 'MAIN_INFO_APE', $setup_infos->ape);
        if ($setup_infos->rcs) dolibarr_set_const($this->db, 'MAIN_INFO_RCS', $setup_infos->rcs);
        if ($setup_infos->cnbe) dolibarr_set_const($this->db, 'MAIN_INFO_CNBE', $setup_infos->cnbe);
        if ($setup_infos->carpa) dolibarr_set_const($this->db, 'MAIN_INFO_CARPA', $setup_infos->carpa);
        if ($setup_infos->object) dolibarr_set_const($this->db, 'MAIN_INFO_SOCIETE_OBJECT', $setup_infos->object);

        // Step 4
        if ($setup_infos->fiscal_month_start) dolibarr_set_const($this->db, 'SOCIETE_FISCAL_MONTH_START', $setup_infos->fiscal_month_start);
        if ($setup_infos->vta_number) dolibarr_set_const($this->db, 'MAIN_INFO_TVAINTRA', $setup_infos->vta_number);
        if ($setup_infos->vta_liable) dolibarr_set_const($this->db, 'FACTURE_TVAOPTION', $setup_infos->vta_liable);

        return $this->get();
    }
}

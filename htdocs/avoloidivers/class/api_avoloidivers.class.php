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
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/api_projects.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/api_thirdparties.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiDivers {@requires user,external}
 */
class AvoloiDivers extends DolibarrApi
{

	/**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
	);

	public $clientFilter = "-1";
	public $prospectFilter = "-1";
	public $tiersFilter = "-1";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}


	/**
	 * Search a tiers by it's name
	 * 
	 * @param   string   $searched
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchtiers
	 */
	public function searchtiers($searched = '', $page = "-1", $limit = "-1", $clientFilter = "-1", $prospectFilter = "-1", $tiersFilter = "-1")
	{
		global $conf, $langs, $user;
		$this->clientFilter = $clientFilter;
		$this->prospectFilter = $prospectFilter;
		$this->tiersFilter = $tiersFilter;

		// Find contacts
		if ($searched) {
			$contacts = $this->getContacts($searched);
		}

		// Find societies
		$societies = $this->getSocieties($searched);

		$scArr = [];
		$conArr = [];

		foreach ($societies as $s) {
			$is_individual = $this->isIndividual($s->id);

			if (!$searched) {
				if ($s->array_options["options_primary_contact"] !== "")
					$tmpContact = $this->getContact($s->array_options["options_primary_contact"]);
				$society = array();
				$society["is_individual"] = $is_individual;
				$society["is_contact"] = false;
				$society["primary_contact"] = $s->array_options["options_primary_contact"];
				$society["contact_firstname"] = $tmpContact && $tmpContact->firstname ? $tmpContact->firstname : null;
				$society["contact_lastname"] = $tmpContact && $tmpContact->lastname ? $tmpContact->lastname : null;
				$society["contact_id"] = $tmpContact && $tmpContact->id ? $tmpContact->id : null;
				$society["society_id"] = $s->id;
				$society["society_name"] = $s->name;
				$society['contact_object'] = $tmpContact;
				$society['society_object'] = $this->getSociety($s->id);

				$scArr[] = $society;
				$tmpContact = null;
			} else {
				$tmpContacts = $this->getContactsOfSociety($s->id);

				foreach ($tmpContacts as $tmpContact) {
					$society = array();
					$society["is_individual"] = $is_individual;
					$society["is_contact"] = false;
					$society["primary_contact"] = $s->array_options["options_primary_contact"];
					$society["contact_firstname"] = $tmpContact && $tmpContact->firstname ? $tmpContact->firstname : null;
					$society["contact_lastname"] = $tmpContact && $tmpContact->lastname ? $tmpContact->lastname : null;
					$society["contact_id"] = $tmpContact && $tmpContact->id ? $tmpContact->id : null;
					$society["society_id"] = $s->id;
					$society["society_name"] = $s->name;
					$society['contact_object'] = $tmpContact;
					$society['society_object'] = $this->getSociety($s->id);

					$scArr[] = $society;
					$tmpContact = null;
				}
			}
		}

		// Filtrer les doublons sur $contacts
		if ($searched) {
			foreach ($contacts as $c) {
				$tmpSoc = $this->getSociety($c->socid);

				$contact = array();
				$contact["is_individual"] = $tmpSoc->array_options["options_is_society"] === '1' ? false : true;
				$contact["is_contact"] = true;
				$contact["primary_contact"] = $tmpSoc->array_options["options_primary_contact"];
				$contact["contact_firstname"] = $c->firstname;
				$contact["contact_lastname"] = $c->lastname;
				$contact["contact_id"] = $c->id;
				$contact["society_id"] = $c->socid;
				$contact["society_name"] = $c->socname;
				$contact['contact_object'] = $c;
				$contact['society_object'] = $tmpSoc;

				$foundDuplicateIndividual = false;
				foreach ($scArr as $sc) {
					if ($contact["society_id"] == $sc["society_id"]) {
						$foundDuplicateIndividual = true;
						break;
					}
				}

				// Si le contact n'est pas le contact d'une societé particulier on peut l'ajouter
				if (!$foundDuplicateIndividual) {
					$conArr[] = $contact;
				}
			}
		}

		$rtdArr = array_merge($scArr, $conArr);

		// Filtrer sur le(s) type(s) de tiers (client, propect, tiers)
		$rtdArr = array_filter($rtdArr, function ($ra) {
			return $this->typeTiersFilter($ra, $this->clientFilter, $this->prospectFilter, $this->tiersFilter);
		});

		// Retirer les doublons
		// if ($searched) {
		// 	$tmp = array_filter($rtdArr, function ($t) {
		// 		if (($t["is_individual"] && !$t["is_contact"]) || (!$t["is_individual"] && !$t["is_contact"])) {
		// 			return false;
		// 		}
		// 		return true;
		// 	});
		// 	$rtdArr = [];
		// 	foreach ($tmp as $t) {
		// 		$rtdArr[] = $t;
		// 	}
		// }
		$result = array();
		$result['total'] = count($rtdArr);
		// Pagination
		if ($page !== -1 && $limit !== -1) {
			$tmppage = (int) $page;
			$tmplimit = (int) $limit;
			$tmp = array_slice($rtdArr, $tmppage * $tmplimit, $tmplimit);

			$rtdArr = [];
			foreach ($tmp as $t) {
				$rtdArr[] = $t;
			}
		}
		$result['tiers'] = $rtdArr;

		return $result;
	}

	/**
	 * Get an affairs by it's id
	 * 
	 * @param   string   $id
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /affair
	 */
	public function affair($id)
	{
		global $conf, $langs, $user, $db;

		$obj_ret = array();
		$sql = " SELECT t.*";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s on t.fk_soc = s.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "projet_extrafields as px on t.rowid = px.fk_object";
		$sql .= " WHERE t.entity IN (1) AND t.rowid = " . $id;

		$result = $db->query($sql);
		// $affair = $this->db->fetch_object($result);

		if ($result) {
			$obj = $db->fetch_object($result);
			$project_static = new Project($db);
			if ($project_static->fetch($obj->rowid)) {
				$obj_ret = $this->_cleanObjectDatas($project_static);
			}
		} else {
			throw new RestException(503, 'Error when retrieve project list : ' . $db->lasterror());
		}

		$affairList = $obj_ret;
		// $result = array();
		// $result['total'] = $total;
		if ($affairList->socid != null && $affairList->socid !== '') {
			$thirdParties = new Thirdparties();
			$thirdParty = $thirdParties->get($affairList->socid);
			$affairList->tiers = array();
			$affairList->tiers['id'] = $thirdParty->id;
			$affairList->tiers['firstname'] = $thirdParty->firstname;
			$affairList->tiers['lastname'] = $thirdParty->lastname;
			$affairList->tiers['name'] = $thirdParty->name;
		}

		if ($affairList && $affairList->array_options && $affairList->array_options->options_multitiers) {
			$affairList->array_options->options_multitiers = json_encode($affairList->array_options->options_multitiers);
			for ($j = 0; $j <= count($affairList->array_options->options_multitiers); $j++) {
				$affairList->array_options->options_multitiers[$j]->detail = $thirdParties->get($affairList->array_options->options_multitiers[$j]->idTiers);
			}
		}
		return $affairList;
	}


	/**
	 * Search an affairs by various filters
	 * 
	 * @param   string   $searched
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchaffairs
	 */
	public function searchaffairs($limit = '-1', $page = '0', $searchFilter = '', $statusStringFilter = '', $dateStartFilter = '', $dateEndFilter = '', $sortfield = "t.rowid", $sortorder = 'ASC')
	{
		global $conf, $langs, $user, $db;

		//decodage des paramètres
		$searchFilter = urldecode($searchFilter);
		$dateStartFilter = urldecode($dateStartFilter);
		$dateEndFilter = urldecode($dateEndFilter);
		$statusStringFilter = urldecode($statusStringFilter);

		$dateStartSql = '';
		$dateEndSql = '';
		$sqlAffairsFiltersArray = [];
		$sqlFiltersArray = [];
		$statusFilter = [];
		if ($statusStringFilter) {
			$statusFilter = preg_split('/[,]+/', $statusStringFilter);
		}

		if ($dateStartFilter !== '') {
			$dateStartSql = '(t.datec >= \'' . $dateStartFilter . '\')';
			array_push($sqlAffairsFiltersArray, $dateStartSql);
		}
		if ($dateEndFilter !== '') {
			$dateEndSql = '(t.datec <= \'' . $dateEndFilter . '\')';
			array_push($sqlAffairsFiltersArray, $dateEndSql);
		}
		if ($searchFilter !== '') {
			$searchSql = '(t.title like \'%' . $searchFilter . '%\')';
			$tiersSql = '(s.nom like \'%' . $searchFilter . '%\')';
			$multitiersSql = '(JSON_EXTRACT(px.multitiers, \'$[*].detail.name\') like \'%' . $searchFilter . '%\')';
			array_push($sqlAffairsFiltersArray, $searchSql);
			array_push($sqlFiltersArray, $tiersSql);
			array_push($sqlFiltersArray, $multitiersSql);
		}

		if (count($statusFilter) > 0) {
			if (!array_search('-1', $statusFilter)) {
				$statusSql = '';
				for ($i = 0; $i < count($statusFilter); $i++) {
					if ($statusSql !== '') {
						$statusSql .= ' OR ';
					}
					$statusSql .=  '(t.fk_statut:=:\'' . $statusFilter[$i] . '\')';
				}
				if ($statusSql !== '') {
					array_push($sqlAffairsFiltersArray, $statusSql);
				}
			}
		}

		$affairsSqlFilters = join(' AND ', $sqlAffairsFiltersArray);
		array_push($sqlFiltersArray, $affairsSqlFilters);
		$sqlFilters = join(' OR ', $sqlFiltersArray);

		$obj_ret = array();
		$sql = "SELECT t.*";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s on t.fk_soc = s.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "projet_extrafields as px on t.rowid = px.fk_object";
		$sql .= " WHERE t.entity IN (1)";

		if ($sqlFilters && $sqlFilters !== '') {
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlFilters) . ")";
		}

		$sql .= $db->order($sortfield, $sortorder);


		$result = $db->query($sql);
		// $affairs = $this->db->fetch_object($result);

		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				$project_static = new Project($db);
				if ($project_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($project_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve project list : ' . $db->lasterror());
		}

		$affairList = $obj_ret;
		$total = count($affairList);
		$result = array();
		$result['total'] = $total;
		for ($i = 0; $i < $total; $i++) {
			if ($affairList[$i]->socid != null && $affairList[$i]->socid !== '') {
				$thirdParties = new Thirdparties();
				$thirdParty = $thirdParties->get($affairList[$i]->socid);
				$affairList[$i]->tiers = array();
				$affairList[$i]->tiers['id'] = $thirdParty->id;
				$affairList[$i]->tiers['firstname'] = $thirdParty->firstname;
				$affairList[$i]->tiers['lastname'] = $thirdParty->lastname;
				$affairList[$i]->tiers['name'] = $thirdParty->name;
			}

			if ($affairList[$i] && $affairList[$i]->array_options && $affairList[$i]->array_options->options_multitiers) {
				$affairList[$i]->array_options->options_multitiers = json_encode($affairList[$i]->array_options->options_multitiers);
				for ($j = 0; $j <= count($affairList[$i]->array_options->options_multitiers); $j++) {
					$affairList[$i]->array_options->options_multitiers[$j]->detail = $thirdParties->get($affairList[$i]->array_options->options_multitiers[$j]->idTiers);
				}
			}
		}

		if ($page !== '-1' && $limit !== '-1') {
			$tmppage = (int) $page;
			$tmplimit = (int) $limit;
			$tmp = array_slice($affairList, $tmppage * $tmplimit, $tmplimit);

			$rtdArr = [];
			foreach ($tmp as $t) {
				$rtdArr[] = $t;
			}
			$result['affairs'] = $rtdArr;
		} else {
			$result['affairs'] = $affairList;
		}
		return $result;
	}

	/**
	 * Search invoices by various filters
	 * 
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchinvoices
	 */
	public function searchinvoices($limit = '-1', $page = '0', $searchFilter = '', $searchType = '0', $statusStringFilter = '', $dateStartFilter = '', $dateEndFilter = '', $sortfield = "t.rowid", $sortorder = 'ASC', $affairId = '', $socId = '')
	{
		global $conf, $langs, $user, $db;

		//decodage des paramètres
		$searchFilter = urldecode($searchFilter);
		$dateStartFilter = urldecode($dateStartFilter);
		$dateEndFilter = urldecode($dateEndFilter);
		$statusStringFilter = urldecode($statusStringFilter);
		$affairIdFilter = urldecode($affairId);
		$socIdFilter = urldecode($socId);

		$dateStartSql = '';
		$dateEndSql = '';
		$sqlInvoicesFiltersArray = [];
		$sqlFiltersArray = [];
		$statusFilter = [];

		if ($affairIdFilter && $affairIdFilter !== '') {
			$affairIdSql = '(t.fk_projet = \'' . $affairIdFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $affairIdSql);
		}

		if ($socIdFilter && $socIdFilter !== '') {
			$socIdSql = '(t.fk_soc = \'' . $socIdFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $socIdSql);
		}

		// Obliger de mettre un flag PREG_SPLIT_NO_EMPTY ici. Lorsque la string ne contenait que '0', preg_split renvoyait tableau vide.
		// Côté front, j'ai mis des virgule à la fin de chaque chiffres comme ça, on à '0,' au lieu de ne récupérer que '0'
		// Le flag permet que le tableau renvoyer n'es pas de valeur vide et donc, au lieu d'obtenir [0,], j'ai juste [0]
		if ($statusStringFilter) {
			$statusFilter = preg_split('/[,]+/', (string) $statusStringFilter, -1, PREG_SPLIT_NO_EMPTY);
		}

		if ($dateStartFilter !== '') {
			$dateStartSql = '(t.datec >= \'' . $dateStartFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $dateStartSql);
		}
		if ($dateEndFilter !== '') {
			$dateEndSql = '(t.datec <= \'' . $dateEndFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $dateEndSql);
		}
		if ($searchFilter !== '') {
			switch ((int) $searchType) {
				case 0:
					$affairSql = '(pj.title like \'%' . $searchFilter . '%\')';
					$tiersSql = '(tex.client like \'%' . $searchFilter . '%\')';
					$temp = array();
					array_push($temp, $affairSql);
					array_push($temp, $tiersSql);
					$searchSql = join(' OR ', $temp);
					array_push($sqlInvoicesFiltersArray, '(' . $searchSql . ')');
					break;
				case 1:
					$affairSql = '(pj.title like \'%' . $searchFilter . '%\')';
					array_push($sqlInvoicesFiltersArray, $affairSql);
					break;
				case 2:
					$tiersSql = '(tex.client like \'%' . $searchFilter . '%\')';
					array_push($sqlInvoicesFiltersArray, $tiersSql);
					break;
			}
		}

		if (count($statusFilter) > 0) {
			// Lorsque array_search ne trouve pas quelque chose, il renvoie un variable qui est soit un boolean,
			// soit qui s'évalue comme un boolean (0), si '-1' se trouve à la 1ère position ($statusFilter[0])
			// alors quand il trouve -1 il renverra 0, sauf que 0 serai interpréter comme false par le if.
			if (array_search('-1', $statusFilter) === false) {
				$statusSql = '';
				for ($i = 0; $i < count($statusFilter); $i++) {
					if ($statusFilter[$i] !== '') {
						if ($statusSql !== '') {
							$statusSql .= ' OR ';
						}
						$statusSql .=  '(t.fk_statut:=:\'' . $statusFilter[$i] . '\')';
					}
				}
				if ($statusSql !== '') {
					array_push($sqlInvoicesFiltersArray, $statusSql);
				}
			}
		}

		$invoicesSqlFilters = join(' AND ', $sqlInvoicesFiltersArray);
		array_push($sqlFiltersArray, $invoicesSqlFilters);
		$sqlFilters = join(' OR ', $sqlFiltersArray);

		$obj_ret = array();
		$sql = "SELECT t.*";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_extrafields as tex on tex.fk_object = t.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "projet as pj on t.fk_projet = pj.rowid";
		$sql .= " WHERE t.entity IN (1)";

		if ($sqlFilters && $sqlFilters !== '') {
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlFilters) . ")";
		}

		$sql .= $db->order($sortfield, $sortorder);

		$result = $db->query($sql);
		if ($result) {
			$i = 0;
			$num = $db->num_rows($result);
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				$invoice_static = new Facture($db);
				if ($invoice_static->fetch($obj->rowid)) {
					// Get payment details
					$invoice_static->totalpaid = $invoice_static->getSommePaiement();
					$invoice_static->totalcreditnotes = $invoice_static->getSumCreditNotesUsed();
					$invoice_static->totaldeposits = $invoice_static->getSumDepositsUsed();
					$invoice_static->remaintopay = price2num($invoice_static->total_ttc - $invoice_static->totalpaid - $invoice_static->totalcreditnotes - $invoice_static->totaldeposits, 'MT');

					// Add external contacts ids
					$invoice_static->contacts_ids = $invoice_static->liste_contact(-1, 'external', 1);

					$obj_ret[] = $this->_cleanObjectDatas($invoice_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve invoice list : ' . $db->lasterror());
		}

		$invoiceList = $obj_ret;

		$total = count($invoiceList);
		$result = array();
		$result['total'] = $total;
		if ($page !== '-1' && $limit !== '-1') {
			$tmppage = (int) $page;
			$tmplimit = (int) $limit;
			$tmp = array_slice($invoiceList, $tmppage * $tmplimit, $tmplimit);

			$rtdArr = [];
			foreach ($tmp as $t) {
				$rtdArr[] = $t;
			}
			$result['invoices'] = $rtdArr;
		} else {
			$result['invoices'] = $invoiceList;
		}
		return $result;
	}

	/**
	 * Search propals by various filters
	 * 
	 * @return  array List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchpropals
	 */
	public function searchpropals($limit = '-1', $page = '0', $searchFilter = '', $statusStringFilter = '', $dateStartFilter = '', $dateEndFilter = '', $sortfield = "t.rowid", $sortorder = 'ASC', $affairId = '', $socId = '')
	{
		global $conf, $langs, $user, $db;

		//decodage des paramètres
		$searchFilter = urldecode($searchFilter);
		$dateStartFilter = urldecode($dateStartFilter);
		$dateEndFilter = urldecode($dateEndFilter);
		$statusStringFilter = urldecode($statusStringFilter);
		$affairIdFilter = urldecode($affairId);
		$socIdFilter = urldecode($socId);

		$dateStartSql = '';
		$dateEndSql = '';
		$sqlInvoicesFiltersArray = [];
		$sqlFiltersArray = [];
		$statusFilter = [];

		if ($affairIdFilter && $affairIdFilter !== '') {
			$affairIdSql = '(t.fk_projet = \'' . $affairIdFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $affairIdSql);
		}

		if ($socIdFilter && $socIdFilter !== '') {
			$socIdSql = '(t.fk_soc = \'' . $socIdFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $socIdSql);
		}

		// Obliger de mettre un flag PREG_SPLIT_NO_EMPTY ici. Lorsque la string ne contenait que '0', preg_split renvoyait tableau vide.
		// Côté front, j'ai mis des virgule à la fin de chaque chiffres comme ça, on à '0,' au lieu de ne récupérer que '0'
		// Le flag permet que le tableau renvoyer n'es pas de valeur vide et donc, au lieu d'obtenir [0,], j'ai juste [0]
		if ($statusStringFilter) {
			$statusFilter = preg_split('/[,]+/', (string) $statusStringFilter, -1, PREG_SPLIT_NO_EMPTY);
		}

		if ($dateStartFilter !== '') {
			$dateStartSql = '(t.datec >= \'' . $dateStartFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $dateStartSql);
		}
		if ($dateEndFilter !== '') {
			$dateEndSql = '(t.datec <= \'' . $dateEndFilter . '\')';
			array_push($sqlInvoicesFiltersArray, $dateEndSql);
		}
		if ($searchFilter !== '') {
			$affairSql = '(pj.title like \'%' . $searchFilter . '%\')';
			$tiersSql = '(tex.client like \'%' . $searchFilter . '%\')';
			// $namePropalSql = '(t.title like \'%' . $searchFilter . '%\')';
			$searchSql = array();
			array_push($searchSql, $affairSql);
			array_push($searchSql, $tiersSql);
			// array_push($searchSql, $namePropalSql);
			$searchSql = join(' OR ', $searchSql);
			array_push($sqlInvoicesFiltersArray, '(' . $searchSql . ')');
		}

		if (count($statusFilter) > 0) {
			// Lorsque array_search ne trouve pas quelque chose, il renvoie un variable qui est soit un boolean,
			// soit qui s'évalue comme un boolean (0), si '-1' se trouve à la 1ère position ($statusFilter[0])
			// alors quand il trouve -1 il renverra 0, sauf que 0 serai interpréter comme false par le if.
			if (array_search('-1', $statusFilter) === false) {
				$statusSql = '';
				for ($i = 0; $i < count($statusFilter); $i++) {
					if ($statusFilter[$i] !== '') {
						if ($statusSql !== '') {
							$statusSql .= ' OR ';
						}
						$statusSql .=  '(t.fk_statut:=:\'' . $statusFilter[$i] . '\')';
					}
				}
				if ($statusSql !== '') {
					array_push($sqlInvoicesFiltersArray, $statusSql);
				}
			}
		}

		$invoicesSqlFilters = join(' AND ', $sqlInvoicesFiltersArray);
		array_push($sqlFiltersArray, $invoicesSqlFilters);
		$sqlFilters = join(' OR ', $sqlFiltersArray);

		$obj_ret = array();
		$sql = "SELECT t.*";
		$sql .= " FROM " . MAIN_DB_PREFIX . "propal as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "propal_extrafields as tex on tex.fk_object = t.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "projet as pj on t.fk_projet = pj.rowid";
		$sql .= " WHERE t.entity IN (1)";

		if ($sqlFilters && $sqlFilters !== '') {
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlFilters) . ")";
		}

		$sql .= $db->order($sortfield, $sortorder);

		$result = $db->query($sql);
		if ($result) {
			$i = 0;
			$num = $db->num_rows($result);
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				$proposal_static = new Propal($db);
				if ($proposal_static->fetch($obj->rowid)) {
					// Add external contacts ids
					$proposal_static->contacts_ids = $proposal_static->liste_contact(-1, 'external', 1);
					$obj_ret[] = $this->_cleanObjectDatas($proposal_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve invoice list : ' . $db->lasterror());
		}

		$invoiceList = $obj_ret;

		$total = count($invoiceList);
		$result = array();
		$result['total'] = $total;
		// echo $page;
		// echo $limit;
		if ($page !== '-1' && $limit !== '-1') {
			$tmppage = (int) $page;
			$tmplimit = (int) $limit;
			$tmp = array_slice($invoiceList, $tmppage * $tmplimit, $tmplimit);

			$rtdArr = [];
			foreach ($tmp as $t) {
				$rtdArr[] = $t;
			}
			$result['proposals'] = $rtdArr;
		} else {
			$result['proposals'] = $invoiceList;
		}
		// print_r($result);
		return $result;
	}

	/**
	 * Search a propal by it's name
	 * 
	 * @param   string   $searched
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchpropalsbyname
	 */
	public function searchpropalsbyname($searched)
	{
		global $conf, $langs, $user;

		$obj_ret = array();

		// TODO Récupérer IDs des propals dans llx_propal_extrafields sur title
		$sql = "SELECT fk_object";
		$sql .= " FROM " . MAIN_DB_PREFIX . "propal_extrafields as p";
		$sql .= " WHERE p.titre LIKE '%" . $searched . "%'";

		$resql = $this->db->query($sql);

		// TODO Récupérer les propals avec les IDs récupérés précédement
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($resql);
				$propals = new Propal($this->db);
				if ($propals->fetch($obj->fk_object)) {
					$obj_ret[] = $this->_cleanObjectDatas($propals);
				}
				$i++;
			}
		}

		// TODO Retourner le résultat
		return $obj_ret;
	}

	private function isIndividual($socid)
	{
		$society = new Societe($this->db);
		$society->fetch($socid);
		$society = $this->_cleanObjectDatas($society);

		return $society->array_options["options_is_society"] === '1' ? false : true;
	}

	public function getSociety($id)
	{
		$society = new Societe($this->db);
		$society->fetch($id);
		return $this->_cleanObjectDatas($society);
	}

	private function getContact($id)
	{
		$contact = new Contact($this->db);
		$contact->fetch($id);
		return $this->_cleanObjectDatas($contact);
	}

	private function typeTiersFilter($soc, $clientFilter, $prospectFilter, $tiersFilter)
	{
		$society = new Societe($this->db);
		$society->fetch($soc["society_id"]);
		$society = $this->_cleanObjectDatas($society);

		if (($clientFilter === -1 && $prospectFilter === -1 && $tiersFilter === -1)
			|| ($clientFilter !== -1 && $society->client === "1")
			|| ($prospectFilter !== -1 && $society->client === "2")
			|| ($tiersFilter !== -1 && $society->client === "0")
		) {
			return true;
		} else {
			return false;;
		}
	}

	private function getContacts($value)
	{
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
		$sql .= " WHERE (c.lastname LIKE '%" . $value . "%')";
		$sql .= " OR (c.firstname LIKE '%" . $value . "%')";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($resql);
				$contacts = new Contact($this->db);
				if ($obj->fk_soc && $contacts->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($contacts);
				}
				$i++;
			}
		}

		return $obj_ret;
	}

	public function getContactsOfSociety($socid)
	{
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
		$sql .= " WHERE (c.fk_soc = $socid)";
		$sql .= " ORDER BY c.lastname ASC";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($resql);
				$contacts = new Contact($this->db);
				if ($obj->fk_soc && $contacts->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($contacts);
				}
				$i++;
			}
		}

		return $obj_ret;
	}

	private function getSocieties($value)
	{
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		$sql .= " WHERE (s.nom LIKE '%" . $value . "%')";
		$sql .= " ORDER BY s.nom ASC";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($resql);
				$societies = new Societe($this->db);
				if ($societies->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($societies);
				}
				$i++;
			}
		}

		return $obj_ret;
	}
}

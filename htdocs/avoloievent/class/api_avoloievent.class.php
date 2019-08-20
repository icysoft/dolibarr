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
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiEvent {@requires user,external}
 */
class AvoloiEvent extends DolibarrApi
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
	 * Create an Avoloi event.
	 * 
	 * @param   array   $avoloi_event
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url POST /createavoloievent
	 */
	public function createavoloievent($avoloi_event)
	{
		global $conf, $langs, $user;

		$avoloi_event = (object) $avoloi_event;

		$agendaevent = $avoloi_event->agendaevent;
		$tiers = $avoloi_event->tiers;
		$affair = $avoloi_event->affair;

		// TODO Checker l'existence du tiers
		// TODO Checker l'existence de l'affaire

		if (sizeof($agendaevent) <= 0) {
			// TODO Return erreur si pas d'information sur l'event
			return "Manque l'enventagenda";
		}

		if (sizeof($tiers) <= 0 && sizeof($affair) > 0) {
			// TODO Return erreur si affaire sans tiers
			return "Tiers obligatoire si affaire présente";
		}

		// if (sizeof($tiers) > 0) {
		// 	$societe = new Societe($this->db);

		// 	$societe->name = $tiers['name'];
		// 	$societe->name_alias = $tiers['name_alias'];
		// 	$societe->client = $tiers['client'];
		// 	$societe->default_lang = $tiers['default_lang'];
		// 	$societe->tva_assuj = $tiers['tva_assuj'];
		// 	$societe->tva_intra = $tiers['tva_intra'];
		// 	$societe->typent_id = $tiers['typent_id'];
		// 	$societe->capital = $tiers['capital'];
		// 	$societe->idprof1 = $tiers['id_prof_1'];
		// 	$societe->idprof2 = $tiers['id_prof_2'];
		// 	$societe->idprof3 = $tiers['id_prof_3'];
		// 	$societe->idprof4 = $tiers['id_prof_4'];
		// 	$societe->idprof5 = $tiers['id_prof_5'];
		// 	$societe->idprof6 = $tiers['id_prof_6'];
		// 	$societe->array_options = $tiers['array_options'];

		// 	$socid = $societe->create($user);
 
			// if (sizeof($affair) > 0 && $socid) {
				$projet = new Project($this->db);

				$projet->title = $affair['title'];
				$projet->description = $affair['description'];
				$projet->socid = $socid;
				$projet->ref = $affair['ref'];
				$projet->statut = $affair['statut'];
				$projet->date_start = $affair['date_start'];
				$projet->date_end = $affair['date_end'];
				$projet->budget_amount = $affair['budget_amount'];
				$projet->opp_percent = $affair['opp_percent'];
				$projet->opp_status = $affair['opp_status'];

				$affairid = $projet->create($user);
				print "AFFAIR ID : $affairid<br>";
			// }
		// }

		// $event = new ActionComm($this->db);

		// $event->socid = $affairid;
		// $event->userownerid = $agendaevent['userownerid'];
		// $event->type_id = $agendaevent['type_id'];
		// $event->datep = $agendaevent['datep'];
		// $event->datef = $agendaevent['datef'];
		// $event->note = $agendaevent['note'];
		// $event->userdoneid = $agendaevent['userdoneid'];
		// $event->contactid = $socid;

		// $eventcreated = $event->create($user);

		// // TODO Envoi de la notification push

		// $rtd = array();

		// $rtd['tiers_id'] = $socid;
		// $rtd['affair_id'] = $affairid;
		// $rtd['event'] = $eventcreated;

		// return $rtd;
	}
}

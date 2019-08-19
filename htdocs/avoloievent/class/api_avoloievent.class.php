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

		print "CREATION D'UN EVENT<br>";

		$agendaevent = $avoloi_event->agendaevent;
		$tiers = $avoloi_event->tiers;
		$affair = $avoloi_event->affair;

		if ($tiers['name'] !== '') {
			$societe = new Societe($this->db);

			$societe->name = $tiers['name'];
			$societe->name_alias = $tiers['name_alias'];
			$societe->client = $tiers['client'];
			$societe->default_lang = $tiers['default_lang'];
			$societe->tva_assuj = $tiers['tva_assuj'];
			$societe->tva_intra = $tiers['tva_intra'];
			$societe->typent_id = $tiers['typent_id'];
			$societe->capital = $tiers['capital'];
			$societe->idprof1 = $tiers['id_prof_1'];
			$societe->idprof2 = $tiers['id_prof_2'];
			$societe->idprof3 = $tiers['id_prof_3'];
			$societe->idprof4 = $tiers['id_prof_4'];
			$societe->idprof5 = $tiers['id_prof_5'];
			$societe->idprof6 = $tiers['id_prof_6'];
			$societe->array_options['options_type_tiers'] = $tiers->array_options['options_type_tiers'];

			$socid = $societe->create($user);

			if ($affair['title'] !== '') {
				// TODO Création d'une affaire
			}
		}

		// TODO Création d'un event

		// $event = new ActionComm($this->db);
		// $event->socid = $socid;
		// $event->create($user);

		// TODO Envoi de la notification push
	}
}

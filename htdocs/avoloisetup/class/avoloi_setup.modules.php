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
 *	\file       htdocs/avoloisetup/class/avoloi_setup.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build Docx documents for third parties
 */

/**
 *	Class to build documents using ODF templates generator
 */
class avoloi_setup
{
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
		print "AVOLOI SETUP - CONSTRUCT<br>";
	}
}

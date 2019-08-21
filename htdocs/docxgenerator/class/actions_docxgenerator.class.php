<?php

class ActionsDocXGenerator
{ 

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
			global $conf, $user, $langs, $db;
			global $hookmanager;
			require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
			if ($parameters['object']) {
				if ($parameters['object']->socid) {
					$sql = "SELECT *";
					$sql .= " FROM ".MAIN_DB_PREFIX."societe as p";
					$sql .= " WHERE p.rowid = ".$parameters['object']->socid;

					$result = $db->query($sql);
					$tiers = $db->fetch_object($result);
				}
				
				if ($parameters['object']->fk_project) {
					$sql = "SELECT *";
					$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
					$sql .= " WHERE p.rowid = ".$parameters['object']->fk_project;

					$result = $db->query($sql);
					$affaire = $db->fetch_object($result);
				}
				if ($parameters['object']->element == 'facture' || $parameters['object']->element == 'invoice') {
					$endname = str_replace('/var/www/documents/facture/', '', $parameters['file']);
					if ($tiers) {
						$path = '/tiers/'.$tiers->nom.'/facture';
						if ($affaire) {
							$path = $path.'/'.$affaire->title;
						}
					} else if ($affaire) {
						$path = '/affaire/'.$affaire->title.'/facture';
					} else {
						$path = '/facture';
					}
					$dirpath = $path.'/'.$parameters['object']->ref;
					if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT.$dirpath))) {
						mkdir($this->sanitizePath(DOL_DATA_ROOT.$dirpath), 0700, true);
					}
					$result = copy($parameters['file'], $this->sanitizePath(DOL_DATA_ROOT.$path.'/'.$endname));
				}
				
				if ($parameters['object']->element == 'propale' || $parameters['object']->element == 'proposal' || $parameters['object']->element == 'propal') {
					$endname = str_replace('/var/www/documents/propale/', '', $parameters['file']);
					if ($tiers) {
						$path = '/tiers/'.$tiers->nom.'/propale';
						if ($affaire) {
							$path = $path.'/'.$affaire->title;
						}
					} else if ($affaire) {
						$path = '/affaire/'.$affaire->title.'/propale';
					} else {
						$path = '/propale';
					}
					$dirpath = $path.'/'.$parameters['object']->ref;
					if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT.$dirpath))) {
						mkdir($this->sanitizePath(DOL_DATA_ROOT.$dirpath), 0700, true);
					}
					$result = copy($parameters['file'], $this->sanitizePath(DOL_DATA_ROOT.$path.'/'.$endname));
				}
			}


			$outputlangs=$langs;

			$ret=0;

			return $ret;
	}

	protected function sanitizePath($str) {
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', '/\s+/'=>'_', ' '=>'_' );
		return strtr($str , $unwanted_array );
	}
}
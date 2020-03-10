<?php
 use Luracast\Restler\RestException;

/**
 * API class for projects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class AvoloiManageRights
{
  /**
   *  Constructor
   *
   *  @param      DoliDB		$db      Database handler
   */
  public function __construct($db)
  {
      $this->db = $db;
  }

  /**
   * Get rights of the user or a group
   * @param string $id            User ID
   * @param string $userorgroup   Indicate if we looking for rights of an user or a group
   * 
   * @return array Return an object with the user's rights
   */
  public function getRights($id, $userorgroup) {
    /**
     * Règles
     * 
     * Groupe : "g"
     * Aucun : "n"
     * Lecture : "r"
     * Ecriture : "w"
     * Total : "a"
     */

    $rtd = array();

    // Get right on tiers
    $rtd['tiers'] = $this->getTiersRight($id, $userorgroup);

    // Get right on projects
    $rtd['affairs'] = $this->getAffairsRight($id, $userorgroup);

    // Get right on invoices
    $rtd['invoices'] = $this->getInvoicesRight($id, $userorgroup);

    // Get right on proposals
    $rtd['proposals'] = $this->getPropalsRight($id, $userorgroup);

    // Get right on agenda events
    $rtd['agenda'] = $this->getAgendaRight($id, $userorgroup);

    // Get right on tasks
    $rtd["tasks"] = $this->getTasksRight($id, $userorgroup);

    return $rtd;
  }

  private function getTiersRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:societe / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:societe / perms:creer / type:w
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:societe / perms:lire / type:r
    // alors il a les droits en lecture "r".
    // Sinon il n'a aucun droit "n".

    $params = array();
    $params["id"] = $id;
    $params["module"] = "societe";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "creer";
    $params["r_perms"] = "lire";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "w";
    $params["r_type"] = "r";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Récupération des droits de l'utilisateur sur les affaires
   * 
   * @return string Le droit de l'utilisateur sur ce domaine.
   */
  private function getAffairsRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:projet / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:projet / perms:creer / type:w
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:projet / perms:lire / type:r
    // alors il a les droits en écriture "r".
    // Sinon il n'a aucun droit "n".

    $params = array();
    $params["id"] = $id;
    $params["module"] = "projet";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "creer";
    $params["r_perms"] = "lire";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "w";
    $params["r_type"] = "r";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Récupération des droits de l'utilisateur sur les factures
   * 
   * @return string Le droit de l'utilisateur sur ce domaine.
   */
  private function getInvoicesRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:facture / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:facture / perms:creer / type:a
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:facture / perms:lire / type:a
    // alors il a les droits en écriture "r".
    // Sinon il n'a aucun droit "n".

    $params = array();
    $params["id"] = $id;
    $params["module"] = "facture";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "creer";
    $params["r_perms"] = "lire";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "a";
    $params["r_type"] = "a";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Récupération des droits de l'utilisateur sur les propositions de convention d'honoraire
   * 
   * @return string Le droit de l'utilisateur sur ce domaine.
   */
  private function getPropalsRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:propale / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:propale / perms:creer / type:w
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:propale / perms:lire / type:r
    // alors il a les droits en écriture "r".
    // Sinon il n'a aucun droit "n".
    // TODO Faire une requête cherchant ces différents droits sur la table "llx_user_rights" avec une jointure sur la table de définition "llx_rights_def"

    $params = array();
    $params["id"] = $id;
    $params["module"] = "propale";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "creer";
    $params["r_perms"] = "lire";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "w";
    $params["r_type"] = "r";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Récupération des droits de l'utilisateur sur les évennements agenda
   * 
   * @return string Le droit de l'utilisateur sur ce domaine.
   */
  private function getAgendaRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:agenda / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:agenda / perms:myactions / subperms:create / type:w
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:agenda / perms:myactions / subperms:read / type:r
    // alors il a les droits en écriture "r".
    // Sinon il n'a aucun droit "n".
    // TODO Faire une requête cherchant ces différents droits sur la table "llx_user_rights" avec une jointure sur la table de définition "llx_rights_def"

    $params = array();
    $params["id"] = $id;
    $params["module"] = "agenda";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "myactions";
    $params["r_perms"] = "myactions";
    $params["w_subperms"] = "create";
    $params["r_subperms"] = "read";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "w";
    $params["r_type"] = "r";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Récupération des droits de l'utilisateur sur les tâches
   * 
   * @return string Le droit de l'utilisateur sur ce domaine.
   */
  private function getTasksRight($id, $userorgroup) {
    // Si l'utilisateur à les droits module:task / perms:group / type:g
    // alors il a les droits de groupe "g".
    // Si l'utilisateur à les droits module:task / perms:creer / type:w
    // alors il a les droits en écriture "w".
    // Si l'utilisateur à les droits module:task / perms:lire / type:r
    // alors il a les droits en écriture "r".
    // Sinon il n'a aucun droit "n".
    // TODO Faire une requête cherchant ces différents droits sur la table "llx_user_rights" avec une jointure sur la table de définition "llx_rights_def"

    $params = array();
    $params["id"] = $id;
    $params["module"] = "task";
    $params["a_perms"] = "total";
    $params["g_perms"] = "group";
    $params["w_perms"] = "creer";
    $params["r_perms"] = "lire";
    $params["a_type"] = "a";
    $params["g_type"] = "g";
    $params["w_type"] = "w";
    $params["r_type"] = "r";
    $sql = $this->concatGetRequest($params, $userorgroup);

    return $this->extractRights($sql);
  }

  /**
   * Concaténation de la requête sur les droits d'un domaine pour un utilisateur
   * @param array $params Object de définition Dolibarr des droits pour un domaine pour un utilisateur
   * 
   * @throws Exception Si l'ID de l'utilisateur n'est pas présent dans le paramètre $params
   * @throws Exception Si le domaine n'est pas présent dans le paramètre $params
   * @throws Exception Si aucune définition de droit n'est présente dans le paramètre $params
   * @return string La requête permettant la récupération des droits d'un domaine pour un utilisateur
   */
  private function concatGetRequest($params, $userorgroup) {
    $id = $params["id"];
    $module = $params["module"];
    $a_perms = $params["a_perms"];
    $g_perms = $params["g_perms"];
    $w_perms = $params["w_perms"];
    $r_perms = $params["r_perms"];
    $g_subperms = $params["g_subperms"];
    $w_subperms = $params["w_subperms"];
    $r_subperms = $params["r_subperms"];
    $a_type = $params["a_type"];
    $g_type = $params["g_type"];
    $w_type = $params["w_type"];
    $r_type = $params["r_type"];

    if (!$id || $id === "") {
      throw new Exception('User ID is missing.');
    }
    if (!$module || $module === "") {
      throw new Exception('Module param is missing.');
    }
    if ((!$a_perms || $a_perms === "")
      && (!$g_perms || $g_perms === "")
      && (!$w_perms || $w_perms === "")
      && (!$r_perms || $r_perms === "")
      && (!$g_subperms || $g_subperms === "")
      && (!$w_subperms || $w_subperms === "")
      && (!$r_subperms || $r_subperms === "")
      && (!$a_type || $a_type === "")
      && (!$g_type || $g_type === "")
      && (!$w_type || $w_type === "")
      && (!$r_type || $r_type === "")) {
      throw new Exception('Several params are missing.');
    }
    if (!$userorgroup || $userorgroup === '') {
      throw new Exception('Don\'t able to determinate if you are looking for rights on group or user');
    }

    $sql = "SELECT CASE ";
    $sql.= "WHEN rd.module='$module' ";
    if ($a_perms && $a_perms !== "") $sql.= "AND rd.perms='$a_perms' ";
    if ($a_subperms && $a_subperms !== "") $sql.= "AND subperms='$a_subperms' ";
    if ($a_type && $a_type !== "") $sql.= "AND rd.type='$a_type' ";
    $sql.= "THEN 'a' ";
    $sql.= "WHEN rd.module='$module' ";
    if ($g_perms && $g_perms !== "") $sql.= "AND rd.perms='$g_perms' ";
    if ($g_subperms && $g_subperms !== "") $sql.= "AND subperms='$g_subperms' ";
    if ($g_type && $g_type !== "") $sql.= "AND rd.type='$g_type' ";
    $sql.= "THEN 'g' ";
    $sql.= "WHEN rd.module='$module' ";
    if ($w_perms && $w_perms !== "") $sql.= "AND rd.perms='$w_perms' ";
    if ($w_subperms && $w_subperms !== "") $sql.= "AND subperms='$w_subperms' ";
    if ($w_type && $w_type !== "") $sql.= "AND rd.type='$w_type' ";
    $sql.= "THEN 'w' ";
    $sql.= "WHEN rd.module='$module' ";
    if ($r_perms && $r_perms !== "") $sql.= "AND rd.perms='$r_perms' ";
    if ($r_subperms && $r_subperms !== "") $sql.= "AND subperms='$r_subperms' ";
    if ($r_type && $r_type !== "") $sql.= "AND rd.type='$r_type' ";
    $sql.= "THEN 'r' ";
    $sql.= "ELSE 'n' ";
    $sql.= "END rights ";
    $sql.= "FROM llx_rights_def as rd ";
    if ($userorgroup === "user") {
      $sql.= "JOIN llx_user_rights as ur ";
      $sql.= "WHERE ur.fk_id=rd.id AND ur.fk_user=$id";
    }
    if ($userorgroup === "group") {
      $sql.= "JOIN llx_usergroup_rights as ur ";
      $sql.= "WHERE ur.fk_id=rd.id AND ur.fk_usergroup=$id";
    }

    return $sql;
  }

  /**
   * Récupération du droit sur un domaine pour un utilisateur
   * @return string Droit dans un domaine pour un utilisateur
   */
  private function extractRights($sql) {
    $result = $this->db->query($sql);

    $rtd = array();
    foreach ($result as $right) {
      $rtd[] = $right['rights'];
    }

    if (in_array('a', $rtd)) {
      return ["right_code" => "a", "right_label" => "RIGHT_TOTAL"];
    } else if (in_array('g', $rtd)) {
      return ["right_code" => "g", "right_label" => "RIGHT_GROUP"];
    } else if (in_array('w', $rtd)) {
      return ["right_code" => "w", "right_label" => "RIGHT_WRITE"];
    } else if (in_array('r', $rtd)) {
      return ["right_code" => "r", "right_label" => "RIGHT_READ"];
    } else {
      return ["right_code" => "n", "right_label" => "RIGHT_NONE"];
    }
  }

  /**
   * Set user rights
   * @param string $id User ID
   * @param array $user_rights Object with the rights to set.
   * 
   * @return array Return an object with the user's rights
   */
  public function setRights($id, $user_rights, $userorgroup) {
    // TODO Donner de base les droits sur les contacts :
    // module:societe / perms:contact ainsi que le droit module:societe / perms:client / type:r

    // Set right on tiers
    $this->setTiersRight($id, $user_rights["tiers"]["right_code"], $userorgroup);

    // Set right on projects
    $this->setAffairsRight($id, $user_rights["affairs"]["right_code"], $userorgroup);

    // Set right on invoices
    $this->setInvoicesRight($id, $user_rights["invoices"]["right_code"], $userorgroup);

    // Set right on proposals
    $this->setPropalsRight($id, $user_rights["proposals"]["right_code"], $userorgroup);

    // Set right on agenda events
    $this->setAgendaRight($id, $user_rights["agenda"]["right_code"], $userorgroup);

    // Set right on task events
    $this->setTaskRight($id, $user_rights["tasks"]["right_code"], $userorgroup);

    // Set rights on GED
    $this->setGEDRights($id, $userorgroup);

    return $this->getRights($id, $userorgroup);
  }
  
  /**
   * Enregistrement des droits de l'utilisateur sur les tiers.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setTiersRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "societe", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "societe", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "societe", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "lire", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "creer", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "export", "type" => "d"];
    } else if ($right === "g") {
      // Setter droit module:societe / perms:group / type:g
      $params[] = ["module" => "societe", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:societe / perms:lire / type:r
      // Setter droit module:societe / perms:creer / type:w
      // Setter droit module:societe / perms:supprimer / type:d
      // Setter droit module:societe / perms:contact / subperms:lire / type:r
      // Setter droit module:societe / perms:contact / subperms:creer / type:w
      // Setter droit module:societe / perms:contact / subperms:supprimer / type:d
      // Setter droit module:societe / perms:contact / subperms:export / type:d
      $params[] = ["module" => "societe", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "lire", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "creer", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "export", "type" => "d"];
    } else if ($right === "r") {
      // Setter droit module:societe / perms:lire / type:r
      // Setter droit module:societe / perms:contact / type:r
      $params[] = ["module" => "societe", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "contact", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
    }
    $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
    $params[] = ["module" => "societe", "perms" => "contact", "subperms" => "lire", "type" => "r"];

    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur les affaires.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setAffairsRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "projet", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "projet", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "projet", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "projet", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "projet", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "projet", "perms" => "all", "subperms" => "lire", "type" => "r"];
    } else if ($right === "g") {
      // Setter droit module:projet / perms:group / type:g
      $params[] = ["module" => "projet", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:projet / perms:lire / type:r
      // Setter droit module:projet / perms:creer / type:w
      // Setter droit module:projet / perms:supprimer / type:d
      $params[] = ["module" => "projet", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "projet", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "projet", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "projet", "perms" => "all", "subperms" => "lire", "type" => "r"];
    } else if ($right === "r") {
      // Setter droit module:projet / perms:lire / type:r
      $params[] = ["module" => "projet", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "projet", "perms" => "all", "subperms" => "lire", "type" => "r"];
    }

    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur les factures.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setInvoicesRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "facture", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "facture", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "lire", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "creer", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "supprimer", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "unvalidate", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "validate", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "send", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "paiement", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "facture", "subperms" => "export", "type" => "r"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "reopen", "type" => "r"];
    } else if ($right === "g") {
      // Setter droit module:facture / perms:group / type:g
      $params[] = ["module" => "facture", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:facture / perms:lire / type:a
      // Setter droit module:facture / perms:creer / type:a
      // Setter droit module:facture / perms:supprimer / type:a
      // Setter droit module:facture / perms:invoice_advance / subperms:unvalidate / type:a
      // Setter droit module:facture / perms:invoice_advance / subperms:validate / type:a
      // Setter droit module:facture / perms:invoice_advance / subperms:send / type:a
      // Setter droit module:facture / perms:paiement / type:a
      // Setter droit module:facture / perms:facture / subperms:export / type:r
      // Setter droit module:facture / perms:invoice_advance / subperms:reopen / type:r
      $params[] = ["module" => "facture", "perms" => "lire", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "creer", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "supprimer", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "unvalidate", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "validate", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "send", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "paiement", "type" => "a"];
      $params[] = ["module" => "facture", "perms" => "facture", "subperms" => "export", "type" => "r"];
      $params[] = ["module" => "facture", "perms" => "invoice_advance", "subperms" => "reopen", "type" => "r"];
    } else if ($right === "r") {
      // Setter droit module:facture / perms:lire / type:a
      $params[] = ["module" => "facture", "perms" => "lire", "type" => "a"];
    }
    
    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur les propositions de convention d'honoraire.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setPropalsRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "propale", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "propale", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "propale", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "propale", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "propale", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "propal_advance", "subperms" => "validate", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "propal_advance", "subperms" => "send", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "cloturer", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "export", "type" => "r"];
    } else if ($right === "g") {
      // Setter droit module:propale / perms:group / type:g
      $params[] = ["module" => "propale", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:propale / perms:lire / type:r
      // Setter droit module:propale / perms:creer / type:w
      // Setter droit module:propale / perms:supprimer / type:d
      // Setter droit module:propale / perms:propal_advance / subperms:validate / type:d
      // Setter droit module:propale / perms:propal_advance / subperms:send / type:d
      // Setter droit module:propale / perms:cloturer / type:d
      // Setter droit module:propale / perms:export / type:r
      $params[] = ["module" => "propale", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "propale", "perms" => "creer", "type" => "w"];
      $params[] = ["module" => "propale", "perms" => "supprimer", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "propal_advance", "subperms" => "validate", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "propal_advance", "subperms" => "send", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "cloturer", "type" => "d"];
      $params[] = ["module" => "propale", "perms" => "export", "type" => "r"];
    } else if ($right === "r") {
      // Setter droit module:propale / perms:lire / type:r
      $params[] = ["module" => "propale", "perms" => "lire", "type" => "r"];
    }
    
    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur les évennements agenda.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setAgendaRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "agenda", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "agenda", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "read", "type" => "r"];
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "create", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "delete", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "read", "type" => "r"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "create", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "delete", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
    } else if ($right === "g") {
      // Setter droit module:agenda / perms:group / type:g
      $params[] = ["module" => "agenda", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:agenda / perms:myactions / subperms:read / type:r
      // Setter droit module:agenda / perms:myactions / subperms:create / type:w
      // Setter droit module:agenda / perms:myactions / subperms:delete / type:w
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "read", "type" => "r"];
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "create", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "allactions", "subperms" => "delete", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "read", "type" => "r"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "create", "type" => "w"];
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "delete", "type" => "w"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
    } else if ($right === "r") {
      // Setter droit module:agenda / perms:myactions / subperms:read / type:r
      $params[] = ["module" => "agenda", "perms" => "myactions", "subperms" => "read", "type" => "r"];
      $params[] = ["module" => "societe", "perms" => "client", "subperms" => "voir", "type" => "r"];
    }
    
    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur les tasks.
   * 
   * @throws Exception Si une erreur survient lors de la suppression ou l'ajout de droits
   */
  private function setTaskRight($id, $right, $userorgroup) {
    // Suppression de tout les droits du domaine pour éviter les droits résiduels
    $delete = $this->removeRightsOnModule($id, "task", $userorgroup);

    $params = array();
    $sql = "";

    if (!$delete) {
      throw new Exception('An error occurs while removing rights');
    }

    // Ici on set les droits selon le type de droit demandé
    if ($right === "a") {
      $params[] = ["module" => "task", "perms" => "total", "type" => "a"];
      $params[] = ["module" => "task", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "task", "perms" => "creer", "type" => "w"];
    } else if ($right === "g") {
      // Setter droit module:task / perms:group / type:g
      $params[] = ["module" => "task", "perms" => "group", "type" => "g"];
    } else if ($right === "w") {
      // Setter droit module:task / perms:lire / type:r
      // Setter droit module:task / perms:creer / type:w
      // Setter droit module:projet / perms:all / subperms:lire / type:r
      $params[] = ["module" => "task", "perms" => "lire", "type" => "r"];
      $params[] = ["module" => "task", "perms" => "creer", "type" => "w"];
    } else if ($right === "r") {
      // Setter droit module:task / perms:lire / type:r
      $params[] = ["module" => "task", "perms" => "lire", "type" => "r"];
    }
    
    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Enregistrement des droits de l'utilisateur sur la GED.
   * 
   * De fait, les droits sont toujours activés, mais on écrase les droits ici pour être certain
   * que les droits soient bel et bien activés.
   */
  private function setGEDRights($id, $userorgroup) {
    $params = array();
    $params[] = ["module" => "ecm", "perms" => "read", "type" => "r"];
    $params[] = ["module" => "ecm", "perms" => "upload", "type" => "w"];
    $params[] = ["module" => "ecm", "perms" => "setup", "type" => "w"];
    
    if (count($params) > 0) {
      $sql = $this->concatSetRequest($id, $params, $userorgroup);

      $result = $this->db->query($sql);
  
      if (!$result) {
        throw new Exception('An error occurs while setting rights');
      }
    }
  }

  /**
   * Suppression des droits d'un utlisateur dans un domaine donné
   * @param string $id ID de l'utilisateur auquel retirer les droits
   * @param string $module Nom du domaine
   * 
   * @throws Exception Si l'ID de l'utilisateur est manquant
   * @throws Exception Si le nom du module est manquant
   * @throws Exception Si le module n'existe pas
   * @return any Résultat de la requête de suppression des droits
   */
  private function removeRightsOnModule($id, $module, $userorgroup) {
    if (!$id || $id === "") {
      throw new Exception('User ID is missing.');
    }

    if (!$module || $module === "") {
      throw new Exception('Module name is missing.');
    }

    // Find IDs of module
    $sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
    $sql.= "WHERE module = '$module'";
    $moduleids = $this->db->query($sql);

    if ($moduleids->num_rows <= 0) {
      throw new Exception('Module does not exists.');
    }

    $idsarr = array();
    foreach ($moduleids as $moduleid) {
      $idsarr[] = "($id, ".$moduleid["id"].")";
    }

    // Suppression des éléments de la table llx_user_rights
    $sql = "DELETE FROM ".MAIN_DB_PREFIX;
    if ($userorgroup === "user") $sql.= "user_rights ";
    if ($userorgroup === "group") $sql.= "usergroup_rights ";
    $sql.= "WHERE (";
    if ($userorgroup === "user") $sql.= "fk_user";
    if ($userorgroup === "group") $sql.= "fk_usergroup";
    $sql.= ", fk_id) IN (";
    $sql.= join(", ", $idsarr);
    $sql.= ")";

    return $this->db->query($sql);
  }

  /**
   * Concaténation de la requête permettant l'ajout de droits à un utilisateur
   * @param string $id ID de l'utilisateur
   * @param array $params Objet de définition des droits pour un domaine donné
   * 
   * @throws Exception Si l'ID de l'utilisateur est manquant
   * @return string La requête concaténée
   */
  private function concatSetRequest($id, $params, $userorgroup) {
    if (!$id || $id === "") {
      throw new Exception('User ID is missing.');
    }

    if (!$userorgroup || $userorgroup === '') {
      throw new Exception('Don\'t able to determinate if you are looking for rights on group or user');
    }

    $rightsids = $this->getRightsIds($params);

    $valuesarr = array();
    foreach ($rightsids as $rightid) {
      $valuesarr[] = "('1', $id, ".$rightid["id"].")";
    }

    $sql = "INSERT IGNORE INTO ";
    if ($userorgroup === "user") {
      $sql.= MAIN_DB_PREFIX."user_rights ";
      $sql.= "(entity, fk_user, fk_id) VALUES ";
    }
    if ($userorgroup === "group") {
      $sql.= MAIN_DB_PREFIX."usergroup_rights ";
      $sql.= "(entity, fk_usergroup, fk_id) VALUES ";
    }
    $sql.= join(", ", $valuesarr);

    return $sql;
  }

  /**
   * Récupération des ID des différents droits d'un domaine
   * @param array $params Objet représentant la définition des droits d'un domaine
   * 
   * @throws Exception Si la propriété "module" n'est pas présente dans le paramètre $params
   * @return any Résultat de la requête
   */
  private function getRightsIds($params) {
    $clausearr = array();

    foreach ($params as $param) {
      if ($param["module"] === "") {
        throw new Exception('Module param is missing.');
      }

      $strtmp = array();
      $strtmp[] = "(module = '".$param["module"]."'";
      if ($param["perms"]) $strtmp[] = "perms = '".$param["perms"]."'";
      if ($param["subperms"]) $strtmp[] = "subperms = '".$param["subperms"]."'";
      if ($param["type"]) $strtmp[] = "type = '".$param["type"]."'";
      $strtmp = join(" AND ", $strtmp);
      $strtmp.= ")";
      $clausearr[] = $strtmp;
    }

    $sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def ";
    $sql.= "WHERE ";
    $sql.= join(" OR ", $clausearr);

    return $this->db->query($sql);
  }


}
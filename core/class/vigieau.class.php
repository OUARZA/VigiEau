<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class vigieau extends eqLogic {  

  
  
  
  
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron()
  */  

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom */
  public static function cronHourly() {
    $cronHeure = config::byKey('cronHeure', __CLASS__);
    if (!empty($cronHeure) && date('G') != $cronHeure) return;
 
    foreach (eqLogic::byType(__CLASS__, true) as $vigieau) {
      $vigieau->pullvigieau();
      sleep(15);
    }  
  }

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
    $codeInseeCommune = $this->getConfiguration('codeInseeCommune');
    //récupération nom commune
    $url = 'https://geo.api.gouv.fr/communes?code='.$codeInseeCommune.'&fields=code,nom,departement';
    $request_http = new com_http($url);
    $request_http->setCURLOPT_HTTPAUTH(CURLAUTH_DIGEST);
    $jsonData=json_decode(trim($request_http->exec()), true);
    if(is_array($jsonData)){
      $nomCommune = $jsonData['0']['nom'];
      $nomDepartement = $jsonData['0']['departement']['nom'];
      $this->setConfiguration('commune', $nomCommune);
      $this->setConfiguration('departement', $nomDepartement);
    } else {
      log::add(__CLASS__, 'error', 'Code INSEE de commune ('.$codeInseeCommune.') invalide');
    }
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    $typeRestriction = $this->getConfiguration('typeRestriction');

    foreach ($this->getCommonCommandDefinitions() as $logicalId => $definition) {
      $this->createOrUpdateInfoCommand($logicalId, $definition);
    }

    $zoneCommandDefinitions = $this->getZoneCommandDefinitions();
    foreach ($zoneCommandDefinitions as $zoneType => $commands) {
      $shouldCreate = $this->shouldBuildZoneCommands($zoneType, $typeRestriction);
      foreach ($commands as $logicalId => $definition) {
        if ($shouldCreate) {
          $this->createOrUpdateInfoCommand($logicalId, $definition);
        } else {
          $this->removeInfoCommand($logicalId);
        }
      }
    }

    $refresh = $this->getCmd(null, 'refresh');
    if (!is_object($refresh)) {
      $refresh = new vigieauCmd();
      $refresh->setName(__('Rafraichir', __FILE__));
    }
    $refresh->setEqLogic_id($this->getId());
    $refresh->setLogicalId('refresh');
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->setOrder(99);
    $refresh->save();
  }

  private function createOrUpdateInfoCommand($logicalId, $definition) {
    $cmd = $this->getCmd(null, $logicalId);
    if (!is_object($cmd)) {
      $cmd = new vigieauCmd();
      $cmd->setLogicalId($logicalId);
      $cmd->setEqLogic_id($this->getId());
      $cmd->setType('info');
    } else {
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId($logicalId);
      $cmd->setType('info');
    }
    if (isset($definition['name'])) {
      $cmd->setName($definition['name']);
    }
    if (isset($definition['subType'])) {
      $cmd->setSubType($definition['subType']);
    }
    if (isset($definition['order'])) {
      $cmd->setOrder($definition['order']);
    }
    $cmd->save();
  }

  private function removeInfoCommand($logicalId) {
    $cmd = $this->getCmd(null, $logicalId);
    if (is_object($cmd)) {
      $cmd->remove();
    }
  }

  private function updateCommandIfExists($logicalId, $value) {
    $cmd = $this->getCmd(null, $logicalId);
    if (is_object($cmd)) {
      $this->checkAndUpdateCmd($logicalId, $value);
    }
  }

  private function getEnabledUsageKeys() {
    $raw = $this->getConfiguration('usageFilterIds', '');
    if (is_array($raw)) {
      $keys = array();
      foreach ($raw as $key => $value) {
        if (is_int($key)) {
          $stringValue = trim((string) $value);
          if ($stringValue !== '') {
            $keys[] = $stringValue;
          }
          continue;
        }
        if ($value === true || $value === 1 || $value === '1' || $value === 'on') {
          $keys[] = (string) $key;
        }
      }
      return array_values(array_unique($keys));
    }
    if (is_string($raw) && $raw !== '') {
      $parts = explode(',', $raw);
      $keys = array();
      foreach ($parts as $part) {
        $trimmed = trim($part);
        if ($trimmed !== '') {
          $keys[] = $trimmed;
        }
      }
      return array_values(array_unique($keys));
    }
    return array();
  }

  private function buildUsageKey($usage) {
    if (!is_array($usage)) {
      return '';
    }
    if (isset($usage['id']) && $usage['id'] !== '' && $usage['id'] !== null) {
      return (string) $usage['id'];
    }
    if (!empty($usage['nom'])) {
      $slug = $this->slugifyUsageLabel($usage['nom']);
      if ($slug !== '') {
        return 'nom_'.$slug;
      }
    }
    if (!empty($usage['thematique'])) {
      $slug = $this->slugifyUsageLabel($usage['thematique']);
      if ($slug !== '') {
        return 'thematique_'.$slug;
      }
    }
    return '';
  }

  private function buildUsageDisplayKey($usage) {
    if (!is_array($usage)) {
      return '';
    }
    if (!empty($usage['nom'])) {
      $slug = $this->slugifyUsageLabel($usage['nom']);
      if ($slug !== '') {
        return 'display_nom_'.$slug;
      }
    }
    if (!empty($usage['thematique'])) {
      $slug = $this->slugifyUsageLabel($usage['thematique']);
      if ($slug !== '') {
        return 'display_thematique_'.$slug;
      }
    }
    if (isset($usage['id']) && $usage['id'] !== '' && $usage['id'] !== null) {
      return 'display_id_'.$usage['id'];
    }
    return '';
  }

  private function normalizeUsageStatusValue($value) {
    if (!is_string($value) && !is_numeric($value)) {
      return '';
    }
    $stringValue = trim((string) $value);
    if ($stringValue === '') {
      return '';
    }
    $normalized = $this->slugifyUsageLabel($stringValue);
    if ($normalized !== '') {
      return $normalized;
    }
    $lower = strtolower($stringValue);
    return str_replace(array(' ', '-'), '_', $lower);
  }

  private function isTruthyValue($value) {
    if (is_bool($value)) {
      return $value;
    }
    if (is_numeric($value)) {
      return (float) $value != 0.0;
    }
    if (is_string($value)) {
      $normalized = strtolower(trim($value));
      return in_array($normalized, array('1', 'true', 'vrai', 'oui', 'yes', 'on'), true);
    }
    return false;
  }

  private function shouldIncludeUsage($usage, $enabledUsageKeys, $audienceField) {
    if (!is_array($usage)) {
      return false;
    }
    if (!empty($enabledUsageKeys)) {
      $usageKey = $this->buildUsageKey($usage);
      if ($usageKey === '' || !in_array($usageKey, $enabledUsageKeys, true)) {
        return false;
      }
    }
    if ($audienceField !== '') {
      $audienceValue = isset($usage[$audienceField]) ? $usage[$audienceField] : false;
      if (!$this->isTruthyValue($audienceValue)) {
        return false;
      }
    }
    return true;
  }

  private function formatUsageMessage($usage) {
    if (!is_array($usage)) {
      return '';
    }
    $nomUsage = isset($usage['nom']) ? trim((string) $usage['nom']) : '';
    $description = isset($usage['description']) ? trim((string) $usage['description']) : '';
    if ($description !== '') {
      $description = str_replace(array("\r\n", "\n", "\r"), ' ', $description);
      $description = preg_replace('/\s+/u', ' ', $description);
    }
    if ($nomUsage === '' && $description === '') {
      return '';
    }
    if ($nomUsage !== '' && $description !== '') {
      return '<b>'.$nomUsage.'</b> : '.$description;
    }
    return $nomUsage !== '' ? $nomUsage : $description;
  }

  private function getUsageDisplayName($usage) {
    if (!is_array($usage)) {
      return '';
    }
    $nom = isset($usage['nom']) ? trim((string) $usage['nom']) : '';
    if ($nom !== '') {
      return $nom;
    }
    $thematique = isset($usage['thematique']) ? trim((string) $usage['thematique']) : '';
    if ($thematique !== '') {
      return $thematique;
    }
    if (isset($usage['id'])) {
      $id = trim((string) $usage['id']);
      if ($id !== '') {
        return sprintf(__('Usage %s', __FILE__), $id);
      }
    }
    return __('Usage inconnu', __FILE__);
  }

  private function isUsageRestricted($usage) {
    if (!is_array($usage)) {
      return false;
    }

    $booleanKeys = array('restriction', 'restrictionActive', 'estInterdiction', 'interdiction', 'estRestriction', 'usageRestreint', 'restriction_en_cours', 'restreint', 'isRestricted');
    foreach ($booleanKeys as $key) {
      if (!array_key_exists($key, $usage)) {
        continue;
      }
      if ($this->isTruthyValue($usage[$key])) {
        return true;
      }
      $value = $usage[$key];
      if ($value === false || $value === 0 || $value === '0') {
        continue;
      }
    }

    $numericKeys = array('niveauRestriction', 'niveau_restriction', 'niveau', 'severite', 'gravite', 'niveauGravite');
    foreach ($numericKeys as $key) {
      if (!array_key_exists($key, $usage)) {
        continue;
      }
      $value = $usage[$key];
      if (is_numeric($value)) {
        if ((float) $value > 0) {
          return true;
        }
        continue;
      }
      $normalized = $this->normalizeUsageStatusValue($value);
      if ($normalized === '') {
        continue;
      }
      if (!in_array($normalized, array('aucune', 'aucun', 'autorise', 'autorisee', 'autorisation', 'sans_restriction', 'non', 'neant'), true)) {
        return true;
      }
    }

    $stringKeys = array('typeRestriction', 'statut', 'status', 'etat', 'phase');
    foreach ($stringKeys as $key) {
      if (!array_key_exists($key, $usage)) {
        continue;
      }
      $normalized = $this->normalizeUsageStatusValue($usage[$key]);
      if ($normalized === '') {
        continue;
      }
      if (!in_array($normalized, array('aucune', 'aucun', 'autorise', 'autorisee', 'autorisation', 'sans_restriction', 'non', 'neant'), true)) {
        return true;
      }
    }

    if (isset($usage['description'])) {
      $description = strtolower((string) $usage['description']);
      if ($description !== '') {
        if (strpos($description, 'interdiction') !== false || strpos($description, 'interdit') !== false) {
          return true;
        }
      }
    }

    return false;
  }

  private function slugifyUsageLabel($label) {
    $label = trim((string) $label);
    if ($label === '') {
      return '';
    }
    $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    if ($normalized !== false && $normalized !== null) {
      $label = $normalized;
    }
    $labelLower = strtolower($label);
    $labelSlug = preg_replace('/[^a-z0-9]+/', '_', $labelLower);
    if (!is_string($labelSlug)) {
      $labelSlug = '';
    }
    $labelSlug = trim($labelSlug, '_');
    if ($labelSlug !== '') {
      return $labelSlug;
    }
    $fallback = strtolower(preg_replace('/\s+/', '_', trim((string) $label)));
    return trim($fallback, '_');
  }

  private function normalizeTypeInfo($typeInfo) {
    $value = trim((string) $typeInfo);
    if ($value === '') {
      return '';
    }
    $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($normalized !== false && $normalized !== null) {
      $value = $normalized;
    }
    $value = strtolower($value);
    $value = str_replace(array(' ', '-'), '_', $value);
    switch ($value) {
      case 'part':
      case 'particulier':
        return 'particulier';
      case 'pro':
      case 'professionnel':
      case 'entreprise':
        return 'entreprise';
      case 'collectivite':
      case 'collectivites':
        return 'collectivites';
      case 'exploitation':
      case 'exploitation_agricole':
      case 'exploitationagricole':
      case 'agricole':
        return 'exploitation_agricole';
      case 'all':
      case 'tout':
        return '';
      default:
        return '';
    }
  }

  private function getAudienceFieldForType($typeInfo) {
    switch ($typeInfo) {
      case 'particulier':
        return 'concerneParticulier';
      case 'entreprise':
        return 'concerneEntreprise';
      case 'collectivites':
        return 'concerneCollectivite';
      case 'exploitation_agricole':
        return 'concerneExploitation';
      default:
        return '';
    }
  }

  public function getUsageOptionsForConfig() {
    $usageGroups = array();
    $usageCommands = array('usages_zone_sup', 'usages_zone_sou', 'usages_zone_aep');
    foreach ($usageCommands as $logicalId) {
      $cmd = $this->getCmd('info', $logicalId);
      if (!is_object($cmd)) {
        continue;
      }
      try {
        $rawValue = $cmd->execCmd();
      } catch (Exception $e) {
        continue;
      }
      if (!is_string($rawValue) || $rawValue === '') {
        continue;
      }
      $decoded = json_decode($rawValue, true);
      if (!is_array($decoded)) {
        continue;
      }
      foreach ($decoded as $usage) {
        if (!is_array($usage)) {
          continue;
        }
        $key = $this->buildUsageKey($usage);
        if ($key === '') {
          continue;
        }
        $displayKey = $this->buildUsageDisplayKey($usage);
        if ($displayKey === '') {
          $displayKey = $key;
        }
        if (!isset($usageGroups[$displayKey])) {
          $usageGroups[$displayKey] = array(
            'displayKey' => $displayKey,
            'keys' => array(),
            'nom' => isset($usage['nom']) ? $usage['nom'] : '',
            'thematique' => isset($usage['thematique']) ? $usage['thematique'] : '',
          );
        } else {
          if ($usageGroups[$displayKey]['nom'] === '' && !empty($usage['nom'])) {
            $usageGroups[$displayKey]['nom'] = $usage['nom'];
          }
          if ($usageGroups[$displayKey]['thematique'] === '' && !empty($usage['thematique'])) {
            $usageGroups[$displayKey]['thematique'] = $usage['thematique'];
          }
        }
        if (!in_array($key, $usageGroups[$displayKey]['keys'], true)) {
          $usageGroups[$displayKey]['keys'][] = $key;
        }
      }
    }
    if (empty($usageGroups)) {
      return array();
    }
    uasort($usageGroups, function ($a, $b) {
      $themeA = isset($a['thematique']) ? strtolower($a['thematique']) : '';
      $themeB = isset($b['thematique']) ? strtolower($b['thematique']) : '';
      if ($themeA === $themeB) {
        return strcmp(isset($a['nom']) ? strtolower($a['nom']) : '', isset($b['nom']) ? strtolower($b['nom']) : '');
      }
      return strcmp($themeA, $themeB);
    });
    $options = array();
    foreach ($usageGroups as $group) {
      $keys = isset($group['keys']) ? array_values($group['keys']) : array();
      if (empty($keys)) {
        continue;
      }
      $options[] = array(
        'displayKey' => isset($group['displayKey']) ? $group['displayKey'] : $keys[0],
        'keys' => $keys,
        'key' => $keys[0],
        'nom' => isset($group['nom']) ? $group['nom'] : '',
        'thematique' => isset($group['thematique']) ? $group['thematique'] : '',
      );
    }
    return $options;
  }

  private function getCommonCommandDefinitions() {
    return array(
      'departement' => array(
        'name' => __('Département', __FILE__),
        'subType' => 'string',
        'order' => 1,
        'default' => ''
      ),
      'commune' => array(
        'name' => __('Commune', __FILE__),
        'subType' => 'string',
        'order' => 2,
        'default' => ''
      ),
      'numero_arrete' => array(
        'name' => __('Numéro arrêté', __FILE__),
        'subType' => 'string',
        'order' => 4,
        'default' => __('Non communiqué', __FILE__)
      ),
      'numero_arrete_cadre' => array(
        'name' => __('Numéro arrêté cadre', __FILE__),
        'subType' => 'string',
        'order' => 5,
        'default' => __('Non communiqué', __FILE__)
      ),
      'date_debut' => array(
        'name' => __('Date début arrêté', __FILE__),
        'subType' => 'string',
        'order' => 6,
        'default' => ''
      ),
      'date_fin' => array(
        'name' => __('Date fin arrêté', __FILE__),
        'subType' => 'string',
        'order' => 7,
        'default' => ''
      ),
      'urlPdf' => array(
        'name' => __('Url arrêté en pdf', __FILE__),
        'subType' => 'string',
        'order' => 8,
        'default' => ''
      ),
      'urlPdfCadre' => array(
        'name' => __('Url arrêté cadre', __FILE__),
        'subType' => 'string',
        'order' => 9,
        'default' => ''
      ),
    );
  }

  private function getZoneCommandDefinitions() {
    return array(
      'SUP' => array(
          'nom_zone_sup' => array(
            'name' => __('Nom zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 9,
            'default' => '',
            'valueKey' => 'nom'
          ),
          'nom_restriction_sup' => array(
            'name' => __('Nom restriction zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 10,
            'default' => '',
            'valueKey' => 'label'
          ),
          'niveau_restriction_sup' => array(
            'name' => __('Niveau restriction zone SUP', __FILE__),
            'subType' => 'numeric',
            'order' => 11,
            'default' => 0,
            'valueKey' => 'niveau'
          ),
          'editorial_zone_sup' => array(
            'name' => __('Editorial zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 12,
            'default' => '',
            'valueKey' => 'editorial'
          ),
          'editorial_zone_sup_restreint' => array(
            'name' => __('Usages restreints zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 13,
            'default' => __('Aucune restriction détectée', __FILE__),
            'valueKey' => 'restrictedEditorial'
          ),
          'niveau_gravite_sup' => array(
            'name' => __('Niveau gravité zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 14,
            'default' => '',
            'valueKey' => 'niveauGravite'
          ),
          'id_zone_sup' => array(
            'name' => __('ID zone SUP', __FILE__),
            'subType' => 'numeric',
            'order' => 15,
            'default' => 0,
            'valueKey' => 'id'
          ),
          'id_sandre_zone_sup' => array(
            'name' => __('ID Sandre zone SUP', __FILE__),
            'subType' => 'numeric',
            'order' => 16,
            'default' => 0,
            'valueKey' => 'idSandre'
          ),
          'code_zone_sup' => array(
            'name' => __('Code zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 17,
            'default' => '',
            'valueKey' => 'code'
          ),
          'type_zone_sup' => array(
            'name' => __('Type zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 18,
            'default' => '',
            'valueKey' => 'type'
          ),
          'ressource_influencee_sup' => array(
            'name' => __('Ressource influencée zone SUP', __FILE__),
            'subType' => 'binary',
            'order' => 19,
            'default' => 0,
            'valueKey' => 'ressourceInfluencee'
          ),
          'usages_zone_sup' => array(
            'name' => __('Usages zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 20,
            'default' => '[]',
            'valueKey' => 'usages'
          ),
          'gid_zone_sup' => array(
            'name' => __('GID zone SUP', __FILE__),
            'subType' => 'numeric',
            'order' => 21,
            'default' => 0,
            'valueKey' => 'gid'
          ),
          'cdzas_zone_sup' => array(
            'name' => __('Code ZAS zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 22,
            'default' => '',
            'valueKey' => 'CdZAS'
          ),
          'lbzas_zone_sup' => array(
            'name' => __('Libellé ZAS zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 23,
            'default' => '',
            'valueKey' => 'LbZAS'
          ),
          'typezas_zone_sup' => array(
            'name' => __('Type ZAS zone SUP', __FILE__),
            'subType' => 'string',
            'order' => 24,
            'default' => '',
            'valueKey' => 'TypeZAS'
          ),
        ),
        'SOU' => array(
        'nom_zone_sou' => array(
          'name' => __('Nom zone SOU', __FILE__),
          'subType' => 'string',
          'order' => 24,
          'default' => '',
          'valueKey' => 'nom'
        ),
        'nom_restriction_sou' => array(
          'name' => __('Nom restriction zone SOU', __FILE__),
          'subType' => 'string',
          'order' => 25,
          'default' => '',
          'valueKey' => 'label'
        ),
        'niveau_restriction_sou' => array(
          'name' => __('Niveau restriction zone SOU', __FILE__),
          'subType' => 'numeric',
          'order' => 26,
          'default' => 0,
          'valueKey' => 'niveau'
        ),
          'editorial_zone_sou' => array(
            'name' => __('Editorial zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 27,
            'default' => '',
            'valueKey' => 'editorial'
          ),
          'editorial_zone_sou_restreint' => array(
            'name' => __('Usages restreints zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 28,
            'default' => __('Aucune restriction détectée', __FILE__),
            'valueKey' => 'restrictedEditorial'
          ),
          'niveau_gravite_sou' => array(
            'name' => __('Niveau gravité zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 29,
            'default' => '',
            'valueKey' => 'niveauGravite'
          ),
          'id_zone_sou' => array(
            'name' => __('ID zone SOU', __FILE__),
            'subType' => 'numeric',
            'order' => 30,
            'default' => 0,
            'valueKey' => 'id'
          ),
          'id_sandre_zone_sou' => array(
            'name' => __('ID Sandre zone SOU', __FILE__),
            'subType' => 'numeric',
            'order' => 31,
            'default' => 0,
            'valueKey' => 'idSandre'
          ),
          'code_zone_sou' => array(
            'name' => __('Code zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 32,
            'default' => '',
            'valueKey' => 'code'
          ),
          'type_zone_sou' => array(
            'name' => __('Type zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 33,
            'default' => '',
            'valueKey' => 'type'
          ),
          'ressource_influencee_sou' => array(
            'name' => __('Ressource influencée zone SOU', __FILE__),
            'subType' => 'binary',
            'order' => 34,
            'default' => 0,
            'valueKey' => 'ressourceInfluencee'
          ),
          'usages_zone_sou' => array(
            'name' => __('Usages zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 35,
            'default' => '[]',
            'valueKey' => 'usages'
          ),
          'gid_zone_sou' => array(
            'name' => __('GID zone SOU', __FILE__),
            'subType' => 'numeric',
            'order' => 36,
            'default' => 0,
            'valueKey' => 'gid'
          ),
          'cdzas_zone_sou' => array(
            'name' => __('Code ZAS zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 37,
            'default' => '',
            'valueKey' => 'CdZAS'
          ),
          'lbzas_zone_sou' => array(
            'name' => __('Libellé ZAS zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 38,
            'default' => '',
            'valueKey' => 'LbZAS'
          ),
          'typezas_zone_sou' => array(
            'name' => __('Type ZAS zone SOU', __FILE__),
            'subType' => 'string',
            'order' => 39,
            'default' => '',
            'valueKey' => 'TypeZAS'
          ),
        ),
      'AEP' => array(
        'nom_zone_aep' => array(
          'name' => __('Nom zone AEP', __FILE__),
          'subType' => 'string',
          'order' => 39,
          'default' => '',
          'valueKey' => 'nom'
        ),
        'nom_restriction_aep' => array(
          'name' => __('Nom restriction zone AEP', __FILE__),
          'subType' => 'string',
          'order' => 40,
          'default' => '',
          'valueKey' => 'label'
        ),
        'niveau_restriction_aep' => array(
          'name' => __('Niveau restriction zone AEP', __FILE__),
          'subType' => 'numeric',
          'order' => 41,
          'default' => 0,
          'valueKey' => 'niveau'
        ),
          'editorial_zone_aep' => array(
            'name' => __('Editorial zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 42,
            'default' => '',
            'valueKey' => 'editorial'
          ),
          'editorial_zone_aep_restreint' => array(
            'name' => __('Usages restreints zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 43,
            'default' => __('Aucune restriction détectée', __FILE__),
            'valueKey' => 'restrictedEditorial'
          ),
          'niveau_gravite_aep' => array(
            'name' => __('Niveau gravité zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 44,
            'default' => '',
            'valueKey' => 'niveauGravite'
          ),
          'id_zone_aep' => array(
            'name' => __('ID zone AEP', __FILE__),
            'subType' => 'numeric',
            'order' => 45,
            'default' => 0,
            'valueKey' => 'id'
          ),
          'id_sandre_zone_aep' => array(
            'name' => __('ID Sandre zone AEP', __FILE__),
            'subType' => 'numeric',
            'order' => 46,
            'default' => 0,
            'valueKey' => 'idSandre'
          ),
          'code_zone_aep' => array(
            'name' => __('Code zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 47,
            'default' => '',
            'valueKey' => 'code'
          ),
          'type_zone_aep' => array(
            'name' => __('Type zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 48,
            'default' => '',
            'valueKey' => 'type'
          ),
          'ressource_influencee_aep' => array(
            'name' => __('Ressource influencée zone AEP', __FILE__),
            'subType' => 'binary',
            'order' => 49,
            'default' => 0,
            'valueKey' => 'ressourceInfluencee'
          ),
          'usages_zone_aep' => array(
            'name' => __('Usages zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 50,
            'default' => '[]',
            'valueKey' => 'usages'
          ),
          'gid_zone_aep' => array(
            'name' => __('GID zone AEP', __FILE__),
            'subType' => 'numeric',
            'order' => 51,
            'default' => 0,
            'valueKey' => 'gid'
          ),
          'cdzas_zone_aep' => array(
            'name' => __('Code ZAS zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 52,
            'default' => '',
            'valueKey' => 'CdZAS'
          ),
          'lbzas_zone_aep' => array(
            'name' => __('Libellé ZAS zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 53,
            'default' => '',
            'valueKey' => 'LbZAS'
          ),
          'typezas_zone_aep' => array(
            'name' => __('Type ZAS zone AEP', __FILE__),
            'subType' => 'string',
            'order' => 54,
            'default' => '',
            'valueKey' => 'TypeZAS'
          ),
      ),
    );
  }

  private function syncUsageCommands($usageCommandPayloads) {
    $existingUsageCommands = array();
    foreach ($this->getCmd('info') as $cmd) {
      if (!is_object($cmd)) {
        continue;
      }
      $logicalId = $cmd->getLogicalId();
      if (!is_string($logicalId)) {
        continue;
      }
      if (strpos($logicalId, 'usage_') === 0) {
        $existingUsageCommands[$logicalId] = $cmd;
      }
    }

    if (!is_array($usageCommandPayloads)) {
      $usageCommandPayloads = array();
    }

    uasort($usageCommandPayloads, function ($a, $b) {
      $nameA = isset($a['displayName']) ? strtolower(trim((string) $a['displayName'])) : '';
      $nameB = isset($b['displayName']) ? strtolower(trim((string) $b['displayName'])) : '';
      return strcmp($nameA, $nameB);
    });

    $expectedLogicalIds = array();
    $index = 0;
    foreach ($usageCommandPayloads as $usageKey => $payload) {
      $usageKeyString = trim((string) $usageKey);
      if ($usageKeyString === '') {
        continue;
      }
      $displayName = isset($payload['displayName']) ? trim((string) $payload['displayName']) : '';
      if ($displayName === '') {
        $displayName = sprintf(__('Usage %s', __FILE__), $usageKeyString);
      }
      $message = isset($payload['message']) ? trim((string) $payload['message']) : '';
      if ($message === '') {
        $message = __('Aucune information disponible', __FILE__);
      }
      $restricted = (isset($payload['restricted']) && $payload['restricted']) ? 1 : 0;

      $baseLogicalId = 'usage_'.$usageKeyString;
      $textLogicalId = $baseLogicalId.'_texte';
      $restrictionLogicalId = $baseLogicalId.'_restriction';

      $textDefinition = array(
        'name' => sprintf(__('Usage - %s', __FILE__), $displayName),
        'subType' => 'string',
        'order' => 200 + ($index * 2),
      );
      $restrictionDefinition = array(
        'name' => sprintf(__('Restriction - %s', __FILE__), $displayName),
        'subType' => 'binary',
        'order' => 200 + ($index * 2) + 1,
      );

      $this->createOrUpdateInfoCommand($textLogicalId, $textDefinition);
      $this->createOrUpdateInfoCommand($restrictionLogicalId, $restrictionDefinition);

      $this->updateCommandIfExists($textLogicalId, $message);
      $this->updateCommandIfExists($restrictionLogicalId, $restricted);

      $expectedLogicalIds[$textLogicalId] = true;
      $expectedLogicalIds[$restrictionLogicalId] = true;
      $index++;
    }

    foreach ($existingUsageCommands as $logicalId => $cmd) {
      if (!isset($expectedLogicalIds[$logicalId]) && is_object($cmd)) {
        $cmd->remove();
      }
    }
  }

  private function shouldBuildZoneCommands($zoneType, $typeRestriction) {
    switch ($zoneType) {
      case 'SUP':
        return in_array($typeRestriction, array('sup', 'all'));
      case 'SOU':
        return in_array($typeRestriction, array('sou', 'all'));
      case 'AEP':
        return in_array($typeRestriction, array('aep', 'all'));
      default:
        return true;
    }
  }

  public function pullvigieau() {
    $date = date('Y-m-d');
	$dateFormat = date('d/m/Y');
    $codeInseeCommune = $this->getConfiguration('codeInseeCommune');
    $typeInfoRaw = $this->getConfiguration('typeInfo');
    $typeInfo = $this->normalizeTypeInfo($typeInfoRaw);
    $typeRestriction = $this->getConfiguration('typeRestriction');
    $eqName = $this->getName();
    log::add(__CLASS__, 'debug', ' ');
    log::add(__CLASS__, 'debug', '*********** VigiEau ['.$eqName.'] ***********');
    
    //récupération nom commune
    $url = 'https://geo.api.gouv.fr/communes?code='.$codeInseeCommune.'&fields=code,nom,departement';
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
 	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);   
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);         
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	$response = curl_exec($ch);
	curl_close($ch);
  	$jsonData = json_decode($response, true);  
    
    if(is_array($jsonData)){
      $nomCommune = $jsonData['0']['nom'];
    } else {
      $nomCommune = 'commune invalide';
      log::add(__CLASS__, 'error', 'Code INSEE de commune ('.$codeInseeCommune.') invalide');
    }

    //récupération info zones VigiEau
    $profil = '';
    $profilMapping = array(
      'particulier' => 'particulier',
      'entreprise' => 'entreprise',
      'collectivites' => 'collectivites',
      'exploitation_agricole' => 'exploitation_agricole',
    );
    if (isset($profilMapping[$typeInfo])) {
      $profil = $profilMapping[$typeInfo];
    }
    $url = 'https://api.vigieau.beta.gouv.fr/api/zones?commune='.$codeInseeCommune;
    if ($profil !== '') {
      $url .= '&profil='.$profil;
    }
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);   
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);         
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	$response = curl_exec($ch);
	curl_close($ch);
  	$jsonData = json_decode($response, true);  
     
    if(!is_array($jsonData)){
        log::add(__CLASS__, 'error', 'le site \'https://api.vigieau.beta.gouv.fr\' renvoie une erreur ou n\'est pas accessible');
    } else {
      //sauvegarde date et heure de récupérations des info VigiEau
      $this->setConfiguration('lastActuVigiEau', time())->save();
      if (count($jsonData) === 0) {
        log::add(__CLASS__, 'info', 'Aucune donnée trouvée à la date du '.$dateFormat. ' pour la commune '.$nomCommune);

        $this->updateCommandIfExists('departement', substr($codeInseeCommune, 0, 2));
        $this->updateCommandIfExists('numero_arrete', 'Aucun arrêté trouvé à la date du '.$dateFormat);
        $this->updateCommandIfExists('numero_arrete_cadre', 'Aucun arrêté trouvé à la date du '.$dateFormat);
        $this->updateCommandIfExists('date_debut', '');
        $this->updateCommandIfExists('date_fin', '');
        $this->updateCommandIfExists('commune', $nomCommune);
        $this->updateCommandIfExists('urlPdf', '');
        $this->updateCommandIfExists('urlPdfCadre', '');

        foreach ($this->getZoneCommandDefinitions() as $zoneType => $commands) {
          foreach ($commands as $logicalId => $definition) {
            $this->updateCommandIfExists($logicalId, isset($definition['default']) ? $definition['default'] : '');
          }
        }
        $this->syncUsageCommands(array());
      } else {
        $levelMapping = array(
          'vigilance' => array('label' => __('Vigilance', __FILE__), 'value' => 1),
          'alerte' => array('label' => __('Alerte', __FILE__), 'value' => 3),
          'alerte_renforcee' => array('label' => __('Alerte renforcée', __FILE__), 'value' => 4),
          'crise' => array('label' => __('Crise', __FILE__), 'value' => 5),
          'crise_renforcee' => array('label' => __('Crise renforcée', __FILE__), 'value' => 5),
          'aucune' => array('label' => __('Aucune restriction', __FILE__), 'value' => 0),
        );

        $enabledUsageKeys = $this->getEnabledUsageKeys();
        $audienceField = $this->getAudienceFieldForType($typeInfo);
        $self = $this;
        $prepareUsageData = function ($usages) use ($enabledUsageKeys, $self, $audienceField) {
          $messages = array();
          $restrictedMessages = array();
          $payloads = array();
          foreach ($usages as $usage) {
            if (!$self->shouldIncludeUsage($usage, $enabledUsageKeys, $audienceField)) {
              continue;
            }
            $message = $self->formatUsageMessage($usage);
            if ($message === '') {
              continue;
            }
            $isRestricted = $self->isUsageRestricted($usage) ? 1 : 0;
            $messages[] = $message;
            if ($isRestricted) {
              $restrictedMessages[] = $message;
            }

            $usageKey = $self->buildUsageKey($usage);
            if ($usageKey === '') {
              continue;
            }
            $displayName = $self->getUsageDisplayName($usage);
            if (!isset($payloads[$usageKey])) {
              $payloads[$usageKey] = array(
                'displayName' => $displayName,
                'message' => $message,
                'restricted' => $isRestricted,
              );
            } else {
              if ($payloads[$usageKey]['message'] === '' && $message !== '') {
                $payloads[$usageKey]['message'] = $message;
              }
              if ($isRestricted) {
                $payloads[$usageKey]['restricted'] = 1;
              }
              if (($payloads[$usageKey]['displayName'] === '' || $payloads[$usageKey]['displayName'] === null) && $displayName !== '') {
                $payloads[$usageKey]['displayName'] = $displayName;
              }
            }
          }

          $editorial = count($messages) === 0 ? __('Aucune information disponible', __FILE__) : implode('<br/><br/>', $messages);
          $restrictedEditorial = count($restrictedMessages) === 0 ? __('Aucune restriction détectée', __FILE__) : implode('<br/><br/>', $restrictedMessages);

          return array(
            'message' => $editorial,
            'restrictedMessage' => $restrictedEditorial,
            'payloads' => $payloads,
          );
        };

        $zoneCommandDefinitions = $this->getZoneCommandDefinitions();
        $zoneValues = array();
        foreach ($zoneCommandDefinitions as $zoneType => $commands) {
          $zoneValues[$zoneType] = array(
            'nom' => '',
            'niveau' => 0,
            'label' => '',
            'editorial' => '',
            'restrictedEditorial' => __('Aucune restriction détectée', __FILE__),
            'niveauGravite' => '',
            'id' => 0,
            'idSandre' => 0,
            'code' => '',
            'type' => '',
            'ressourceInfluencee' => 0,
            'usages' => '[]',
            'gid' => 0,
            'CdZAS' => '',
            'LbZAS' => '',
            'TypeZAS' => '',
          );
        }

        $usageCommandPayloads = array();

        $codeInseeDepartement = substr($codeInseeCommune, 0, 2);
        $dateDebutValiditeArrete = '';
        $dateFinValiditeArrete = '';
        $defaultNumeroArrete = __('Non communiqué', __FILE__);
        $numeroArrete = $defaultNumeroArrete;
        $numeroArreteCadre = $defaultNumeroArrete;
        $urlPdf = '';
        $urlPdfCadre = '';

        foreach ($jsonData as $zone) {
          if (!is_array($zone)) {
            continue;
          }

          if (!empty($zone['departement'])) {
            $codeInseeDepartement = $zone['departement'];
          }

          if (isset($zone['arrete']) && is_array($zone['arrete'])) {
            $arrete = $zone['arrete'];
            if ($dateDebutValiditeArrete === '' && !empty($arrete['dateDebutValidite'])) {
              $dateDebutValiditeArrete = date('d/m/Y', strtotime($arrete['dateDebutValidite']));
            }
            if ($dateFinValiditeArrete === '' && !empty($arrete['dateFinValidite'])) {
              $dateFinValiditeArrete = date('d/m/Y', strtotime($arrete['dateFinValidite']));
            }
            if ($numeroArrete === $defaultNumeroArrete && !empty($arrete['id'])) {
              $numeroArrete = $arrete['id'];
            }
            if ($urlPdf === '' && !empty($arrete['cheminFichier'])) {
              $urlPdf = $arrete['cheminFichier'];
            }
            if ($urlPdfCadre === '' && !empty($arrete['cheminFichierArreteCadre'])) {
              $urlPdfCadre = $arrete['cheminFichierArreteCadre'];
            }
            if ($numeroArreteCadre === $defaultNumeroArrete) {
              if (!empty($arrete['idArreteCadre'])) {
                $numeroArreteCadre = $arrete['idArreteCadre'];
              } elseif (!empty($arrete['cheminFichierArreteCadre'])) {
                $path = parse_url($arrete['cheminFichierArreteCadre'], PHP_URL_PATH);
                if (is_string($path) && preg_match('#/(\d+)/[^/]+$#', $path, $matches)) {
                  $numeroArreteCadre = $matches[1];
                }
              }
            }
          }

          $typeZone = isset($zone['type']) ? strtoupper($zone['type']) : '';
          if (!isset($zoneValues[$typeZone])) {
            continue;
          }

          $nomZone = isset($zone['nom']) ? $zone['nom'] : '';
          $niveauGraviteRaw = isset($zone['niveauGravite']) ? $zone['niveauGravite'] : '';
          $niveauGraviteKey = strtolower($niveauGraviteRaw);
          $usages = isset($zone['usages']) && is_array($zone['usages']) ? $zone['usages'] : array();

          $niveauRestriction = 0;
          $nomNiveau = '';
          if (isset($levelMapping[$niveauGraviteKey])) {
            $niveauRestriction = $levelMapping[$niveauGraviteKey]['value'];
            $nomNiveau = $levelMapping[$niveauGraviteKey]['label'];
          } elseif ($niveauGraviteRaw !== '') {
            $niveauRestriction = 0;
            $nomNiveau = ucfirst($niveauGraviteKey);
          }

          $usageData = $prepareUsageData($usages);
          $editorial = isset($usageData['message']) ? $usageData['message'] : __('Aucune information disponible', __FILE__);
          $restrictedEditorial = isset($usageData['restrictedMessage']) ? $usageData['restrictedMessage'] : __('Aucune restriction détectée', __FILE__);
          $usagePayloads = array();
          if (isset($usageData['payloads']) && is_array($usageData['payloads'])) {
            $usagePayloads = $usageData['payloads'];
          }
          foreach ($usagePayloads as $usageKey => $payload) {
            if (!is_array($payload)) {
              continue;
            }
            if (!isset($usageCommandPayloads[$usageKey])) {
              $usageCommandPayloads[$usageKey] = $payload;
              continue;
            }
            if (isset($payload['message']) && trim((string) $payload['message']) !== '') {
              if (!isset($usageCommandPayloads[$usageKey]['message']) || trim((string) $usageCommandPayloads[$usageKey]['message']) === '') {
                $usageCommandPayloads[$usageKey]['message'] = $payload['message'];
              }
            }
            if (!empty($payload['restricted'])) {
              $usageCommandPayloads[$usageKey]['restricted'] = 1;
            }
            if (isset($payload['displayName']) && trim((string) $payload['displayName']) !== '') {
              if (!isset($usageCommandPayloads[$usageKey]['displayName']) || trim((string) $usageCommandPayloads[$usageKey]['displayName']) === '') {
                $usageCommandPayloads[$usageKey]['displayName'] = $payload['displayName'];
              }
            }
          }
          $usagesJson = '[]';
          if (!empty($usages)) {
            $usagesJsonEncoded = json_encode($usages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($usagesJsonEncoded !== false) {
              $usagesJson = $usagesJsonEncoded;
            }
          }

          log::add(__CLASS__, 'debug', '----------'.strtoupper($nomZone.' ['.$typeZone.']').'----------');
          log::add(__CLASS__, 'debug', 'Niveau >> '.$nomNiveau.' ('.$niveauRestriction.')');
          log::add(__CLASS__, 'debug', strip_tags(str_replace('<br/>', ' | ', $editorial)));
          log::add(__CLASS__, 'debug', 'Restreints >> '.strip_tags(str_replace('<br/>', ' | ', $restrictedEditorial)));

          $zoneValues[$typeZone] = array(
            'nom' => $nomZone,
            'niveau' => $niveauRestriction,
            'label' => $nomNiveau,
            'editorial' => $editorial,
            'restrictedEditorial' => $restrictedEditorial,
            'niveauGravite' => $niveauGraviteRaw,
            'id' => isset($zone['id']) ? intval($zone['id']) : 0,
            'idSandre' => isset($zone['idSandre']) ? intval($zone['idSandre']) : 0,
            'code' => isset($zone['code']) ? $zone['code'] : '',
            'type' => isset($zone['type']) ? $zone['type'] : $typeZone,
            'ressourceInfluencee' => !empty($zone['ressourceInfluencee']) ? 1 : 0,
            'usages' => $usagesJson,
            'gid' => isset($zone['gid']) ? intval($zone['gid']) : 0,
            'CdZAS' => isset($zone['CdZAS']) ? $zone['CdZAS'] : '',
            'LbZAS' => isset($zone['LbZAS']) ? $zone['LbZAS'] : '',
            'TypeZAS' => isset($zone['TypeZAS']) ? $zone['TypeZAS'] : '',
          );
        }

        log::add(__CLASS__, 'debug', 'Département            : '.$codeInseeDepartement);
        log::add(__CLASS__, 'debug', 'Numéro arrêté          : '.$numeroArrete);
        log::add(__CLASS__, 'debug', 'Numéro arrêté cadre    : '.$numeroArreteCadre);
        log::add(__CLASS__, 'debug', 'Début validité arrêté  : '.$dateDebutValiditeArrete);
        log::add(__CLASS__, 'debug', 'Fin validité arrêté    : '.$dateFinValiditeArrete);
        log::add(__CLASS__, 'debug', 'Commune                : '.$nomCommune);
        log::add(__CLASS__, 'debug', 'url pdf arrêté         : '.$urlPdf);
        log::add(__CLASS__, 'debug', 'url pdf arrêté cadre   : '.$urlPdfCadre);

        $this->syncUsageCommands($usageCommandPayloads);

        $this->updateCommandIfExists('departement', $codeInseeDepartement);
        $this->updateCommandIfExists('numero_arrete', $numeroArrete);
        $this->updateCommandIfExists('numero_arrete_cadre', $numeroArreteCadre);
        $this->updateCommandIfExists('date_debut', $dateDebutValiditeArrete);
        $this->updateCommandIfExists('date_fin', $dateFinValiditeArrete);
        $this->updateCommandIfExists('commune', $nomCommune);
        $this->updateCommandIfExists('urlPdf', $urlPdf);
        $this->updateCommandIfExists('urlPdfCadre', $urlPdfCadre);

        foreach ($zoneCommandDefinitions as $zoneType => $commands) {
          $zoneData = isset($zoneValues[$zoneType]) ? $zoneValues[$zoneType] : array();
          foreach ($commands as $logicalId => $definition) {
            $valueKey = isset($definition['valueKey']) ? $definition['valueKey'] : null;
            $value = $valueKey !== null && isset($zoneData[$valueKey]) ? $zoneData[$valueKey] : (isset($definition['default']) ? $definition['default'] : '');
            $this->updateCommandIfExists($logicalId, $value);
          }
        }
      }
    }
  }
  
  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /** Permet de modifier l'affichage du widget (également utilisable par les commandes)*/
  public function toHtml($_version = 'dashboard') {
  	$typeRestriction = $this->getConfiguration('typeRestriction'); //récupération de la valeur pour afficher le bon template
    /* 
    // a n'utiliser que si dans la config de l'eqLogic, on laisse le choix a l'user d'utiliser le widget du plugin, ou les widget par défaut du core
     if ($this->getConfiguration('widgetTemplate') != 1) {
      return parent::toHtml($_version);
    } 
    */
    $this->emptyCacheWidget(); // a utiliser qu'en environnement de dev.
    $replace = $this->preToHtml($_version); // initialise les tag standards : #id#, #name# ...

    if (!is_array($replace)) {
      return $replace;
    }

    $version = jeedom::versionAlias($_version);

    foreach ($this->getCmd('info') as $cmd) { // recherche toute les cmd de type info
      $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd(); //initialise les tag en fonction du logicalId
      //gestion de l'affichage de l'échelle en couleurs
      $replace['#nom_restriction_sou_N1#'] = '';
      $replace['#nom_restriction_sou_N2#'] = '';
      $replace['#nom_restriction_sou_N3#'] = '';
      $replace['#nom_restriction_sou_N4#'] = '';
      $replace['#nom_restriction_sou_N5#'] = '';

      switch ($replace['#niveau_restriction_sou#']) {
        case 0:
          $replace['#nom_restriction_sou_N1#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 1:
          $replace['#nom_restriction_sou_N2#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 3:
          $replace['#nom_restriction_sou_N3#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 4:
          $replace['#nom_restriction_sou_N4#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 5:
          $replace['#nom_restriction_sou_N5#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
      }

      $replace['#nom_restriction_sup_N1#'] = '';
      $replace['#nom_restriction_sup_N2#'] = '';
      $replace['#nom_restriction_sup_N3#'] = '';
      $replace['#nom_restriction_sup_N4#'] = '';
      $replace['#nom_restriction_sup_N5#'] = '';

      switch ($replace['#niveau_restriction_sup#']) {
        case 0:
          $replace['#nom_restriction_sup_N1#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 1:
          $replace['#nom_restriction_sup_N2#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 3:
          $replace['#nom_restriction_sup_N3#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 4:
          $replace['#nom_restriction_sup_N4#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 5:
          $replace['#nom_restriction_sup_N5#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
      }

      $replace['#nom_restriction_aep_N1#'] = '';
      $replace['#nom_restriction_aep_N2#'] = '';
      $replace['#nom_restriction_aep_N3#'] = '';
      $replace['#nom_restriction_aep_N4#'] = '';
      $replace['#nom_restriction_aep_N5#'] = '';

      switch ($replace['#niveau_restriction_aep#']) {
        case 0:
          $replace['#nom_restriction_aep_N1#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 1:
          $replace['#nom_restriction_aep_N2#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 3:
          $replace['#nom_restriction_aep_N3#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 4:
          $replace['#nom_restriction_aep_N4#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
        case 5:
          $replace['#nom_restriction_aep_N5#'] = '<center><i class="fab fa-mixer"></i></center>';
          break;
      }

    }
    $lastActuVigiEau = $this->getConfiguration('lastActuVigiEau','');
    $replace['#lastActuVigiEau#'] = 'Données VigiEau importées le '.date('d/m/Y à H:i:s', $lastActuVigiEau);

    /* plusieurs lignes séparées pour comprendre */
    if ($typeRestriction == 'sup') {
      $getTemplate = getTemplate('core', $version, 'vigieau_sup.template', __CLASS__); // on récupère le template 'vigieau.template' du plugin.
      $template_replace = template_replace($replace, $getTemplate); // on remplace les tags
      $postToHtml = $this->postToHtml($_version,$template_replace); // on met en cache le widget, si la config de l'user le permet.
      return $postToHtml; // renvoie le code du template du widget.
    }
    if ($typeRestriction == 'sou') {
      $getTemplate = getTemplate('core', $version, 'vigieau_sou.template', __CLASS__); // on récupère le template 'vigieau.template' du plugin.
      $template_replace = template_replace($replace, $getTemplate); // on remplace les tags
      $postToHtml = $this->postToHtml($_version,$template_replace); // on met en cache le widget, si la config de l'user le permet.
      return $postToHtml; // renvoie le code du template du widget.
    }
    if ($typeRestriction == 'aep') {
      $getTemplate = getTemplate('core', $version, 'vigieau_aep.template', __CLASS__); // on récupère le template 'vigieau.template' du plugin.
      $template_replace = template_replace($replace, $getTemplate); // on remplace les tags
      $postToHtml = $this->postToHtml($_version,$template_replace); // on met en cache le widget, si la config de l'user le permet.
      return $postToHtml; // renvoie le code du template du widget.
    }
    if ($typeRestriction == 'all') {
      $getTemplate = getTemplate('core', $version, 'vigieau_all.template', __CLASS__); // on récupère le template 'vigieau.template' du plugin.
      $template_replace = template_replace($replace, $getTemplate); // on remplace les tags
      $postToHtml = $this->postToHtml($_version,$template_replace); // on met en cache le widget, si la config de l'user le permet.
      return $postToHtml; // renvoie le code du template du widget.
    }
  
  /* 
  // Ces 4 lignes ci-dessus peuvent être concaténer comme ceci : 
  return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'vigieau.template' , __CLASS__)));
  */
  
  }

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*     * **********************Getteur Setteur*************************** */

}

class vigieauCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  	$eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
  	switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
    	case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .
    	$eqlogic->pullvigieau();
     	break;
  	}
  }

  /*     * **********************Getteur Setteur*************************** */

}

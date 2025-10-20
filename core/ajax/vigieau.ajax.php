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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

    switch (init('action')) {
      case 'getUsageOptions':
        $eqId = init('id');
        if (empty($eqId)) {
          throw new Exception(__('Identifiant d\'équipement manquant', __FILE__));
        }
        $eqLogic = vigieau::byId($eqId);
        if (!is_object($eqLogic)) {
          throw new Exception(__('Équipement introuvable', __FILE__));
        }
        $options = $eqLogic->getUsageOptionsForConfig();
        ajax::success($options);
        break;
      case 'searchCommunes':
        $postalCode = trim((string) init('codePostal'));
        $codeInsee = trim((string) init('codeInsee'));
        if ($postalCode === '' && $codeInsee === '') {
          throw new Exception(__('Aucun critère de recherche fourni', __FILE__));
        }

        $queryUrl = null;
        if ($postalCode !== '') {
          if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            ajax::success([]);
          }
          $queryUrl = 'https://geo.api.gouv.fr/communes?codePostal=' . urlencode($postalCode) . '&fields=nom,code';
        } else {
          if (!preg_match('/^[0-9A-Za-z]{5}$/', $codeInsee)) {
            ajax::success([]);
          }
          $queryUrl = 'https://geo.api.gouv.fr/communes?code=' . urlencode($codeInsee) . '&fields=nom,code,codesPostaux';
        }

        $client = new com_http($queryUrl);
        $client->setTimeout(10);
        $response = $client->exec();
        if ($response === false || $response === null) {
          ajax::success([]);
        }
        $decoded = json_decode(trim($response), true);
        if (!is_array($decoded)) {
          ajax::success([]);
        }

        $communes = [];
        if (isset($decoded['code'])) {
          $decoded = [$decoded];
        }
        foreach ($decoded as $commune) {
          if (!is_array($commune)) {
            continue;
          }
          $code = isset($commune['code']) ? trim((string) $commune['code']) : '';
          $nom = isset($commune['nom']) ? trim((string) $commune['nom']) : '';
          if ($code === '' || $nom === '') {
            continue;
          }
          $entry = ['code' => $code, 'nom' => $nom];
          if (isset($commune['codesPostaux']) && is_array($commune['codesPostaux'])) {
            $entry['codesPostaux'] = array_values(array_filter(array_map('strval', $commune['codesPostaux'])));
          }
          $communes[] = $entry;
        }

        ajax::success($communes);
        break;
      default:
        throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    }
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

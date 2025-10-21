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
      case 'getInsee':
        $zipCode = init('zipCode');
        if (!preg_match('/^\d{5}$/', $zipCode)) {
            ajax::error('Le code postal doit avoir 5 chiffres');
        }

        $url = 'https://geo.api.gouv.fr/communes?codePostal=' . urlencode($zipCode) . '&fields=nom,code,codeEpci&format=json';
        $response = @file_get_contents($url);

        if ($response === false) {
            ajax::error('Erreur lors de l’appel à l’API geo');
        }

        $communes = json_decode($response, true);
        $formatted = array_map(function ($commune) {
            return [
                'code' => $commune['code'],
                'codeEpci' => $commune['codeEpci'],
                'nom' => $commune['nom']
            ];
        }, $communes);

        ajax::success($formatted);
        break;
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
      default:
        throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    }
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

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

function vigieauTryLoadComHttp() {
    if (class_exists('com_http')) {
        return true;
    }
    $comHttpPaths = [
        dirname(__FILE__) . '/../../../../core/class/com_http.class.php',
        dirname(__FILE__) . '/../../../../core/php/com_http.php',
    ];
    foreach ($comHttpPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            if (class_exists('com_http')) {
                return true;
            }
        }
    }
    if (function_exists('include_file')) {
        @include_file('core', 'com_http', 'class');
    }
    return class_exists('com_http');
}

function vigieauHttpRequest($url, &$errorMessage = null) {
    $errorMessage = null;

    if (!is_string($url) || $url === '') {
        $errorMessage = __('URL de requête invalide.', __FILE__);
        return null;
    }

    if (vigieauTryLoadComHttp()) {
        try {
            $client = new com_http($url);
            if (method_exists($client, 'setTimeout')) {
                $client->setTimeout(10);
            }
            if (method_exists($client, 'setFollowLocation')) {
                $client->setFollowLocation(1);
            }
            if (method_exists($client, 'setUserAgent')) {
                $client->setUserAgent('vigieau-plugin');
            }
            $response = $client->exec();
            if ($response !== false && $response !== null) {
                return $response;
            }
            if (method_exists($client, 'getError')) {
                $candidateError = trim((string) $client->getError());
                if ($candidateError !== '') {
                    $errorMessage = 'com_http: ' . $candidateError;
                }
            }
        } catch (Exception $e) {
            $errorMessage = 'com_http: ' . $e->getMessage();
            log::add('vigieau', 'debug', 'searchCommunes via com_http (Exception): ' . $e->getMessage());
        }
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FAILONERROR => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                CURLOPT_USERAGENT => 'vigieau-plugin',
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === false || ($httpCode >= 400 && $httpCode !== 0)) {
                $errno = curl_errno($ch);
                $curlError = curl_error($ch);
                $sslErrorCodes = [];
                foreach (['CURLE_SSL_CACERT', 'CURLE_PEER_FAILED_VERIFICATION', 'CURLE_SSL_CERTPROBLEM', 'CURLE_SSL_CONNECT_ERROR'] as $constantName) {
                    if (defined($constantName)) {
                        $sslErrorCodes[] = constant($constantName);
                    }
                }
                if ($response === false && !empty($sslErrorCodes) && in_array($errno, $sslErrorCodes, true)) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($response === false) {
                        $curlError = curl_error($ch);
                    }
                }
                if ($response === false || ($httpCode >= 400 && $httpCode !== 0)) {
                    $errorMessage = 'cURL: ' . ($curlError !== '' ? $curlError : ('HTTP ' . $httpCode));
                    log::add('vigieau', 'debug', 'searchCommunes via curl: ' . $errorMessage);
                    $response = null;
                }
            }
            curl_close($ch);
            if ($response !== null) {
                return $response;
            }
        }
    }

    if (function_exists('file_get_contents') && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "User-Agent: vigieau-plugin\r\nAccept: application/json\r\n",
            ],
            'https' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "User-Agent: vigieau-plugin\r\nAccept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
        if (function_exists('error_clear_last')) {
            error_clear_last();
        }
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $headerLine) {
                    if (stripos($headerLine, 'HTTP/') === 0) {
                        $parts = explode(' ', $headerLine);
                        if (isset($parts[1]) && (int) $parts[1] >= 400) {
                            $errorMessage = 'HTTP ' . $parts[1];
                            log::add('vigieau', 'debug', 'searchCommunes via file_get_contents failed with header: ' . $headerLine);
                            return null;
                        }
                        break;
                    }
                }
            }
            return $response;
        }
        $lastError = error_get_last();
        if (is_array($lastError) && isset($lastError['message'])) {
            $errorMessage = 'stream: ' . $lastError['message'];
        }
        log::add('vigieau', 'debug', 'searchCommunes via file_get_contents failed.');
    }

    if ($errorMessage === null || $errorMessage === '') {
        $errorMessage = __('Aucune méthode HTTP disponible pour joindre le service externe.', __FILE__);
    }

    return null;
}

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

    $action = init('action');
    if ($action === null || $action === '') {
      if (isset($_POST['action'])) {
        $action = $_POST['action'];
      } elseif (isset($_GET['action'])) {
        $action = $_GET['action'];
      }
    }
    $action = trim((string) $action);

    if ($action === '') {
      ajax::success([]);
      die();
    }

    switch ($action) {
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
        $postalCode = init('codePostal');
        if ($postalCode === null || $postalCode === '') {
          if (isset($_POST['codePostal'])) {
            $postalCode = $_POST['codePostal'];
          } elseif (isset($_GET['codePostal'])) {
            $postalCode = $_GET['codePostal'];
          }
        }
        $postalCode = trim((string) $postalCode);

        $codeInsee = init('codeInsee');
        if ($codeInsee === null || $codeInsee === '') {
          if (isset($_POST['codeInsee'])) {
            $codeInsee = $_POST['codeInsee'];
          } elseif (isset($_GET['codeInsee'])) {
            $codeInsee = $_GET['codeInsee'];
          }
        }
        $codeInsee = trim((string) $codeInsee);

        if ($postalCode === '' && $codeInsee === '') {
          ajax::success([]);
          die();
        }

        $queryUrl = null;
        if ($postalCode !== '') {
          if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            ajax::success([]);
            die();
          }
          $queryUrl = 'https://geo.api.gouv.fr/communes?codePostal=' . urlencode($postalCode) . '&fields=nom,code';
        } else {
          if (!preg_match('/^[0-9A-Za-z]{5}$/', $codeInsee)) {
            ajax::success([]);
            die();
          }
          $queryUrl = 'https://geo.api.gouv.fr/communes?code=' . urlencode($codeInsee) . '&fields=nom,code,codesPostaux';
        }

        if ($queryUrl === null) {
          ajax::success([]);
          die();
        }

        $httpError = null;
        $response = vigieauHttpRequest($queryUrl, $httpError);
        if ($response === null) {
          ajax::success([
            'communes' => [],
            'error' => ($httpError !== null && $httpError !== '') ? $httpError : __('Impossible de contacter le service de recherche de communes.', __FILE__),
          ]);
          die();
        }

        $decoded = json_decode(trim($response), true);
        if (!is_array($decoded)) {
          log::add('vigieau', 'debug', 'searchCommunes: réponse invalide depuis ' . $queryUrl . ' => ' . substr($response, 0, 200));
          ajax::error(__('Réponse invalide du service de recherche de communes.', __FILE__), 0);
          die();
        }

        if (isset($decoded['code']) && isset($decoded['nom']) && !isset($decoded[0])) {
          $decoded = [$decoded];
        } elseif (array_values($decoded) !== $decoded) {
          $decoded = [];
        }

        $communes = [];
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

        ajax::success([
          'communes' => $communes,
        ]);
        break;
      default:
        ajax::error(__('Aucune méthode correspondante à', __FILE__) . ' : ' . $action, 0);
        die();
    }
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

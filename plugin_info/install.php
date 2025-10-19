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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
function vigieau_install() {
  $cronHour = config::byKey('cronConfHeure', 'vigieau');
  if ($cronHour === '' || $cronHour === null) {
    $cronHour = rand(0, 23);
    config::save('cronConfHeure', $cronHour, 'vigieau');
  }

  $cronMinute = config::byKey('cronConfMinute', 'vigieau');
  if ($cronMinute === '' || $cronMinute === null) {
    $cronMinute = rand(0, 59);
    config::save('cronConfMinute', $cronMinute, 'vigieau');
  }

  require_once dirname(__FILE__) . '/../core/class/vigieau.class.php';
  vigieau::ensureDailyCron();

  config::save('captcha-warning', 1, 'vigieau');
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function vigieau_update() {
  $cronHour = config::byKey('cronConfHeure', 'vigieau');
  if ($cronHour === '' || $cronHour === null) {
    $cronHour = rand(0, 23);
    config::save('cronConfHeure', $cronHour, 'vigieau');
  }

  $cronMinute = config::byKey('cronConfMinute', 'vigieau');
  if ($cronMinute === '' || $cronMinute === null) {
    $cronMinute = rand(0, 59);
    config::save('cronConfMinute', $cronMinute, 'vigieau');
  }

  require_once dirname(__FILE__) . '/../core/class/vigieau.class.php';
  vigieau::ensureDailyCron();

  config::save('captcha-warning', 1, 'vigieau');
}

// Fonction exécutée automatiquement après la suppression du plugin
function vigieau_remove() {
  $cron = cron::byClassAndFunction('vigieau', 'cron');
  if (is_object($cron)) {
    $cron->remove();
  }
}

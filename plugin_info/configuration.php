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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}

require_once dirname(__FILE__) . '/../core/class/vigieau.class.php';

$cronHour = config::byKey('cronConfHeure', 'vigieau', '');
$cronMinute = config::byKey('cronConfMinute', 'vigieau', '');

if ($cronHour === '' || $cronHour === null) {
  $legacyHour = config::byKey('cronHeure', 'vigieau', '');
  if ($legacyHour !== '' && $legacyHour !== null) {
    $cronHour = (int) $legacyHour;
  } else {
    $cronHour = rand(0, 23);
  }
  config::save('cronConfHeure', $cronHour, 'vigieau');
}

if ($cronMinute === '' || $cronMinute === null) {
  $cronMinute = rand(0, 59);
  config::save('cronConfMinute', $cronMinute, 'vigieau');
}

vigieau::ensureDailyCron();
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">
        {{Heure de mise à jour}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Heure à laquelle le plugin va chercher les informations}}"></i></sup>
      </label>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cronConfHeure">
          <?php
          for ($hour = 0; $hour < 24; $hour++) {
            $selected = ((int) $cronHour === $hour) ? ' selected' : '';
            echo '<option value="' . $hour . '"' . $selected . '>' . str_pad($hour, 2, '0', STR_PAD_LEFT) . 'h</option>';
          }
          ?>
        </select>
      </div>
      <div class="col-md-1">
        <select class="configKey form-control" data-l1key="cronConfMinute">
          <?php
          for ($minute = 0; $minute < 60; $minute++) {
            $selected = ((int) $cronMinute === $minute) ? ' selected' : '';
            echo '<option value="' . $minute . '"' . $selected . '>' . str_pad($minute, 2, '0', STR_PAD_LEFT) . 'min</option>';
          }
          ?>
        </select>
      </div>
    </div>
  </fieldset>
</form>

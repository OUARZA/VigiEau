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

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

 $('.eqLogicAttr[data-l1key=configuration][data-l2key=datasource]').on('change',function(){
    $('.datasource').hide();
    $('.datasource.'+$(this).value()).show();
});

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} };
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td>';
    	tr += '<span class="cmdAttr" data-l1key="id" title="' + init(_cmd.logicalId) + '"></span>';
    tr += '</td>';
    
   	tr += '<td>';
    tr += '<div class="input-group" style="width: 90%;">';
    	tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom}}">';
    	tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
    	tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
    tr += '</div>';
  	
  	if (_cmd.type == 'action' && _cmd.value != ''){
      tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" disabled style="margin-top:5px;width: calc(90% - 35px);display:none" title="{{Commande info liée}}">';
      tr += '<option value="">{{Aucune}}</option>';
      tr += '</select>';
    }
    tr += '</td>';

    tr += '<td>';
    //tr += '<span class="type" type="' + init(_cmd.type) + '" style="display:none;" data-l1key="type">' + jeedom.cmd.availableType() + '</span>'
    tr += '<span class=" cmdAttr type" type="' + init(_cmd.type) + '" data-l1key="type"  style="display:none;"></span>'
  	tr += '<span class=" cmdAttr subType" subType="' + init(_cmd.subType) + '" data-l1key="subType"></span>'
  	tr += '</td>';

  
  
  	tr += '<td>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
    if (_cmd.subType == "binary") {
    	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized">{{Historiser}}</label> ';
    	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> ';
    }
  	else if (_cmd.subType == "numeric") {
	  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
    }
  	else if (_cmd.subType == "slider") {
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%; max-width: 60px;display:inline-block;margin-left: 10px;">';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width: 60px;display:inline-block;margin-left:2px;">';
        
    }
	tr += '</td>';
  	tr += '<td>';
    if (typeof jeeFrontEnd !== 'undefined' && jeeFrontEnd.jeedomVersion !== 'undefined') {
        var cmdCible_Name = "";
        if (_cmd.type == 'action' && _cmd.value != undefined){        
            
        }
      	tr += '<span class="cmdAttr" data-l1key="htmlstate">'+cmdCible_Name+'</span>';
        
    }
	tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    }
  	if (_cmd.type == 'action'){        
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</td>';
    tr += '</tr>';

    if (_cmd.type == 'info'){
        $('#table_infos tbody').append(tr)
        $('#table_infos tbody tr:last').setValues(_cmd, '.cmdAttr')
      	//$('#table_infos tbody tr:last').find('.cmdAttr[data-l1key=type],.cmdAttr[data-l1key=subType]').prop("disabled", true);
        //if (isset(_cmd.type)) $('#table_infos tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type))
        //jeedom.cmd.changeType($('#table_infos tbody tr:last'), init(_cmd.subType))
    }
    else{
     	$('#table_actions tbody').append(tr)
        const $tr = $('#table_actions tbody tr:last');
    	if(_cmd.value != null && _cmd.value != ''){
          	jeedom.eqLogic.buildSelectCmd({
                id: $('.eqLogicAttr[data-l1key=id]').value(),
                filter: { type: 'info' },
                error: function (error) {
                    $('#div_alert').showAlert({ message: error.message, level: 'danger' });
                },
                success: function (result) {
                    $tr.find('.cmdAttr[data-l1key=value]').append(result);//.show();
                    //jeedom.cmd.changeType($tr, init(_cmd.subType));
                    //$tr.find('.cmdAttr[data-l1key=type],.cmdAttr[data-l1key=subType]').prop("disabled", true);
                }
            })
          /////////
          	jeedom.cmd.getHumanCmdName({
                id: _cmd.value,
                success: function (data) {
                    cmdCible_Name = data.replace(/#/g, "").split("][").pop().replace(/]/g, "");
                  	var spanValue = '<span id="cmdCible" data-cmd_id="' + _cmd.value + '">'+cmdCible_Name+'</span>'
                    $tr.find('.cmdAttr[data-l1key=htmlstate]').html(spanValue);
                }
            })
          	        
        }
      	$tr.setValues(_cmd, '.cmdAttr');
            
      	/**/
      
      /*jeedom.cmd.byId({
			id: _cmd.value,
			success: function (data) {
                console.log("cmd_cible: " + data.name)
            }
        })*/
    }
}

var vigieauUsageFilterManager = {
  currentEqId: null,
  refresh: function (force) {
    var $eqIdInput = $('.eqLogicAttr[data-l1key=id]');
    if ($eqIdInput.length === 0) {
      return;
    }
    var eqId = $eqIdInput.value();
    if (!eqId) {
      this.currentEqId = null;
      this.render([], this.getSelectedKeysFromConfig(), true);
      return;
    }
    if (!force && this.currentEqId === eqId) {
      this.applySelection(this.getSelectedKeysFromConfig());
      return;
    }
    var self = this;
    $.ajax({
      type: 'POST',
      url: 'plugins/vigieau/core/ajax/vigieau.ajax.php',
      dataType: 'json',
      data: {
        action: 'getUsageOptions',
        id: eqId
      },
      error: function () {
        self.renderError("Impossible de récupérer la liste des usages. Veuillez rafraîchir l'équipement.");
      },
      success: function (data) {
        if (!data || data.state !== 'ok') {
          var message = (data && data.result) ? data.result : "Erreur lors de la récupération des usages.";
          self.renderError(message);
          return;
        }
        self.currentEqId = eqId;
        var options = $.isArray(data.result) ? data.result : [];
        self.render(options, self.getSelectedKeysFromConfig(), false);
      }
    });
  },
  render: function (options, selectedKeys, isInitial) {
    var $container = $('#usageFilterCheckboxes');
    if ($container.length === 0) {
      return;
    }
    $container.empty();
    var storedKeys = $.isArray(selectedKeys) ? selectedKeys : [];
    if (!options.length) {
      var infoMessage = isInitial ? 'La liste des usages sera disponible après un premier rafraîchissement des données.' : "Aucun usage n\'a été trouvé pour cet équipement.";
      $container.append($('<div class="alert alert-info"></div>').text(infoMessage));
      this.syncHiddenFromCheckboxes(true, storedKeys);
      return;
    }
    var lastThematique = null;
    for (var i = 0; i < options.length; i++) {
      var option = options[i];
      var thematique = option.thematique || '';
      if (thematique !== lastThematique) {
        var $heading = $('<div class="usage-filter-heading"></div>').text(thematique !== '' ? thematique : 'Autres usages');
        $container.append($heading);
        lastThematique = thematique;
      }
      var keys = [];
      if ($.isArray(option.keys)) {
        for (var k = 0; k < option.keys.length; k++) {
          var candidate = option.keys[k];
          if (typeof candidate === 'string' || typeof candidate === 'number') {
            var normalized = $.trim(String(candidate));
            if (normalized !== '' && keys.indexOf(normalized) === -1) {
              keys.push(normalized);
            }
          }
        }
      }
      if (!keys.length && option.key) {
        keys.push(String(option.key));
      }
      if (!keys.length) {
        continue;
      }
      var displayKey = option.displayKey || keys[0];
      var checkboxId = 'usage-filter-' + displayKey;
      var $label = $('<label class="checkbox-inline usage-filter-option"></label>').attr('for', checkboxId);
      var $checkbox = $('<input type="checkbox" class="usage-filter-checkbox" />').attr('id', checkboxId).attr('data-usage-keys', keys.join(','));
      $label.append($checkbox);
      var nom = option.nom || keys[0];
      $label.append(document.createTextNode(' ' + nom));
      $container.append($label);
    }
    this.applySelection(storedKeys);
  },
  applySelection: function (selectedKeys) {
    var $container = $('#usageFilterCheckboxes');
    if ($container.length === 0) {
      return;
    }
    var hasStoredSelection = $.isArray(selectedKeys) && selectedKeys.length > 0;
    var selectedMap = {};
    if (hasStoredSelection) {
      for (var i = 0; i < selectedKeys.length; i++) {
        var selectedKey = $.trim(String(selectedKeys[i]));
        if (selectedKey !== '') {
          selectedMap[selectedKey] = true;
        }
      }
    }
    $container.find('.usage-filter-checkbox').each(function () {
      var $checkbox = $(this);
      var optionKeys = vigieauUsageFilterManager.parseKeys($checkbox.attr('data-usage-keys'));
      if (!optionKeys.length) {
        $checkbox.prop('checked', false);
        return;
      }
      if (hasStoredSelection) {
        var shouldCheck = false;
        for (var j = 0; j < optionKeys.length; j++) {
          if (selectedMap[optionKeys[j]]) {
            shouldCheck = true;
            break;
          }
        }
        $checkbox.prop('checked', shouldCheck);
      } else {
        $checkbox.prop('checked', true);
      }
    });
    this.syncHiddenFromCheckboxes(true, selectedKeys);
  },
  syncHiddenFromCheckboxes: function (isInitial, initialKeys) {
    var $container = $('#usageFilterCheckboxes');
    var $hidden = $('#usageFilterIds');
    if ($container.length === 0 || $hidden.length === 0) {
      return;
    }
    var initialArray = $.isArray(initialKeys) ? initialKeys : [];
    var allKeysMap = {};
    var checkedKeysMap = {};
    $container.find('.usage-filter-checkbox').each(function () {
      var $checkbox = $(this);
      var keys = vigieauUsageFilterManager.parseKeys($checkbox.attr('data-usage-keys'));
      if (!keys.length) {
        return;
      }
      var isChecked = $checkbox.prop('checked');
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (key === '') {
          continue;
        }
        allKeysMap[key] = true;
        if (isChecked) {
          checkedKeysMap[key] = true;
        }
      }
    });
    var allKeys = Object.keys(allKeysMap);
    var checkedKeys = Object.keys(checkedKeysMap);
    if (allKeys.length === 0) {
      $hidden.value('');
      return;
    }
    if (isInitial === true && initialArray.length === 0 && checkedKeys.length === allKeys.length) {
      $hidden.value('');
      return;
    }
    if (checkedKeys.length === allKeys.length) {
      $hidden.value('');
    } else {
      $hidden.value(checkedKeys.join(','));
    }
  },
  getSelectedKeysFromConfig: function () {
    var $hidden = $('#usageFilterIds');
    if ($hidden.length === 0) {
      return [];
    }
    var raw = $hidden.value();
    if (typeof raw !== 'string' || raw === '') {
      return [];
    }
    var parts = raw.split(',');
    var keys = [];
    for (var i = 0; i < parts.length; i++) {
      var key = $.trim(parts[i]);
      if (key !== '') {
        keys.push(key);
      }
    }
    return keys;
  },
  parseKeys: function (raw) {
    var keys = [];
    if (typeof raw !== 'string' || raw === '') {
      return keys;
    }
    var parts = raw.split(',');
    for (var i = 0; i < parts.length; i++) {
      var key = $.trim(parts[i]);
      if (key !== '') {
        if (keys.indexOf(key) === -1) {
          keys.push(key);
        }
      }
    }
    return keys;
  },
  renderError: function (message) {
    var $container = $('#usageFilterCheckboxes');
    if ($container.length === 0) {
      return;
    }
    $container.empty().append($('<div class="alert alert-danger"></div>').text(message));
  }
};

$(document).on('change', '.eqLogicAttr[data-l1key=id]', function () {
  vigieauUsageFilterManager.currentEqId = null;
  vigieauUsageFilterManager.refresh(true);
});

$(document).on('click', '#usageFilterReload', function (e) {
  e.preventDefault();
  vigieauUsageFilterManager.currentEqId = null;
  vigieauUsageFilterManager.refresh(true);
});

$(document).on('click', '#usageFilterSelectAll', function (e) {
  e.preventDefault();
  var $container = $('#usageFilterCheckboxes');
  $container.find('.usage-filter-checkbox').prop('checked', true);
  vigieauUsageFilterManager.syncHiddenFromCheckboxes(false);
});

$(document).on('click', '#usageFilterClear', function (e) {
  e.preventDefault();
  var $container = $('#usageFilterCheckboxes');
  $container.find('.usage-filter-checkbox').prop('checked', false);
  vigieauUsageFilterManager.syncHiddenFromCheckboxes(false);
});

$(document).on('change', '#usageFilterCheckboxes .usage-filter-checkbox', function () {
  vigieauUsageFilterManager.syncHiddenFromCheckboxes(false);
});

$(document).ready(function () {
  vigieauUsageFilterManager.refresh(false);
});


  var isCitySelectorOpen = false;

  function isValidZip(zip) {
    return /^\d{5}$/.test(zip);
  }
  function showZipSuccess(message) {
    // console.log("showZipError appelé");
    const zipFeedback = document.getElementById('zipFeedback');
    zipFeedback.textContent = '✅ ' + message;
    zipFeedback.style.color = 'green';
    // Efface le message après 3 secondes
    setTimeout(() => {
      if (zipFeedback.textContent.startsWith('✅')) clearZipFeedback();
      }, 1000);
  }
  function showZipError(message) {
    // console.log("showZipError appelé");
    const zipFeedback = document.getElementById('zipFeedback');
    zipFeedback.textContent = '❌ '+ message;
    zipFeedback.style.color = 'red';
    document.getElementById('zipCode').style.boxShadow = '0 0 0 2px red';
  }
  function clearZipFeedback() {
    // console.log("clearZipFeedback appelé");
    document.getElementById('zipFeedback').textContent = '';
    document.getElementById('zipCode').style.boxShadow = '';
  }

  function fetchCity(zipCode) {
    fetch('plugins/vigieau/core/ajax/vigieau.ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `action=getInsee&zipCode=${encodeURIComponent(zipCode)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.state !== 'ok') {
        showZipError(data.result || 'Erreur lors de la récupération.');
        return;
      }
      const communes = data.result;
      if (communes.length === 0) {
        showZipError('Aucune commune trouvée pour ce code postal.');
        return;
      }
      // ✅ S'il n'y a qu'une seule commune, on la sélectionne automatiquement
      if (communes.length === 1) {
        const city = communes[0];
        document.getElementById('codeZone').value = `${city.nom} (${city.code},${city.codeEpci ?? '----'})`;
        showZipSuccess("Commune automatiquement renseignée.");
        return;
      }

      // Sinon, on affiche la modale pour choisir
      openCitySelector(communes, function (city) {
        document.getElementById('codeZone').value = `${city.nom} (${city.code},${city.codeEpci ?? '----'})`;
      });
    })
    .catch(error => {
      showZipError(error);
      console.error(error);
    });
  }

  document.getElementById('zipCode').addEventListener('input', function () {
    clearZipFeedback();
  });

  document.getElementById('btnGetInsee').addEventListener('click', function () {
    fetchCity(document.getElementById('zipCode').value.trim());
  });

  document.getElementById('zipCode').addEventListener('keydown', function(event) {
    if (event.target && event.key === 'Enter') {
      event.preventDefault();
      const zip = event.target.value.trim();
      if (!isValidZip(zip)) {
        showZipError('Code postal sur 5 chiffres.');
        return;
      }
      clearZipFeedback();
      fetchCity(zip);
    }
  });

function openCitySelector(communes, onSelect) {
  if (isCitySelectorOpen) return; // empêche d'ouvrir plusieurs modales
  isCitySelectorOpen = true;
  let selectedIndex = 0; // Par défaut, on commence sur le premier élément de la liste.
  
  const listHtml = communes.map((commune, index) =>
    `<li data-index="${index}" style="padding:2px; cursor:pointer; list-style:none; border-bottom:1px solid var(--link-color);">${commune.nom}</li>`
  ).join('');
  
  const dialog = bootbox.dialog({
    title: 'Sélectionnez une commune',
    message: `<ul id="commune-list" style="padding-left:0;">${listHtml}</ul>`,
    buttons: {
      cancel: {
        label: 'Annuler',
        className: 'btn-default'
      },
      ok: {
        label: 'OK',
        className: 'btn-primary',
        callback: function () {
          const city = communes[selectedIndex];
          if (!city) {
            console.warn('Erreur: aucune commune sélectionnée.');
            return false; // Ne pas fermer la modale si aucune sélection.
          }
          onSelect(city); // Appeler le callback avec la commune sélectionnée.
        }
      }
    },
    onShown: function () {
      const listItems = document.querySelectorAll('#commune-list li');
      const list = document.getElementById('commune-list');
      
      if (listItems.length > 0) {
          // Par défaut, mettre en surbrillance l'élément sélectionné
        highlightItem(listItems[selectedIndex]);
          // Ajouter les événements de clic et double-clic sur les éléments de la liste
        listItems.forEach((li, idx) => {
          li.addEventListener('click', () => {
            selectedIndex = idx;
            highlightItem(li);
          });
          li.addEventListener('dblclick', () => {
            const city = communes[idx];
            if (!city) return; // Vérifier que la commune est valide
            selectedIndex = idx;
            dialog.modal('hide');
            onSelect(city); // Sélectionner la commune en double-cliquant
          });
        });
            // Ajout de la détection par survol (roulette ou souris)
        list.addEventListener('mousemove', function (e) {
          const hovered = [...listItems].find(li => {
            const rect = li.getBoundingClientRect();
            return e.clientY >= rect.top && e.clientY <= rect.bottom;
          });
          if (hovered) {
            selectedIndex = parseInt(hovered.dataset.index, 10);
            highlightItem(hovered);
          }
        });
          // Gestion des touches (flèches et Entrée)
        document.addEventListener('keydown', handleKey);
      }
        // Fonction pour mettre en surbrillance l'élément sélectionné
      function highlightItem(li) {
        listItems.forEach(el => el.style.backgroundColor = '');
        li.style.backgroundColor = '#88888840'; // Highlight léger
      }
        // Fonction de gestion des touches (flèches, entrée, échappement)
      function handleKey(e) {
        if (e.key === 'ArrowDown') {
          selectedIndex = Math.min(selectedIndex + 1, listItems.length - 1);
          highlightItem(listItems[selectedIndex]);
        } else if (e.key === 'ArrowUp') {
          selectedIndex = Math.max(selectedIndex - 1, 0);
          highlightItem(listItems[selectedIndex]);
        } else if (e.key === 'Enter') {
          dialog.modal('hide');
          const city = communes[selectedIndex];
          if (city) onSelect(city); // Appel du callback
        } else if (e.key === 'Escape') {
          dialog.modal('hide');
        }
      }

      // Nettoyer après la fermeture de la modale
      dialog.on('hidden.bs.modal', () => {
        isCitySelectorOpen = false;
        document.removeEventListener('keydown', handleKey);
      });
    }
  });
}

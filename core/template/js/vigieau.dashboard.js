(function (window, document) {
  'use strict';

  function toArray(collection) {
    return Array.prototype.slice.call(collection || []);
  }

  function normalizeSpace(value) {
    return (value || '').replace(/\s+/g, ' ').trim();
  }

  function htmlToText(html) {
    if (!html) {
      return '';
    }
    var helper = document.createElement('div');
    helper.innerHTML = html;
    return normalizeSpace(helper.textContent || helper.innerText || '');
  }

  function buildDetailFromNode(node) {
    if (!node) {
      return null;
    }
    var clone = node.cloneNode(true);
    var rawHtml = clone.innerHTML.trim();
    if (rawHtml === '' && normalizeSpace(clone.textContent) === '') {
      return null;
    }

    var title = '';
    var bodyHtml = '';
    var heading = clone.querySelector('strong, b');
    if (heading) {
      title = normalizeSpace(heading.textContent || '');
      heading.parentNode.removeChild(heading);
      while (clone.firstChild && clone.firstChild.nodeType === Node.TEXT_NODE && normalizeSpace(clone.firstChild.textContent) === '') {
        clone.removeChild(clone.firstChild);
      }
      if (clone.firstChild && clone.firstChild.nodeName === 'BR') {
        clone.removeChild(clone.firstChild);
      }
      bodyHtml = clone.innerHTML.trim();
    } else if (rawHtml !== '') {
      var split = rawHtml.split(/<br\s*\/?>/i);
      if (split.length > 1) {
        title = htmlToText(split.shift());
        bodyHtml = split.join('<br>').replace(/^(<br\s*\/?>)+/i, '').trim();
      } else {
        title = htmlToText(rawHtml);
        bodyHtml = '';
      }
    } else {
      title = normalizeSpace(clone.textContent || '');
    }

    title = normalizeSpace(title).replace(/^[\-\u2022:\s]+/, '');
    if (title === '') {
      title = htmlToText(bodyHtml);
    }
    if (title === '') {
      return null;
    }

    var details = document.createElement('details');
    details.className = 'mesure-item';

    var summary = document.createElement('summary');
    summary.className = 'mesure-summary';
    summary.textContent = title;
    details.appendChild(summary);

    if (bodyHtml !== '') {
      var body = document.createElement('div');
      body.className = 'mesure-body';
      body.innerHTML = bodyHtml;
      details.appendChild(body);
    }

    return details;
  }

  function enhanceContent(content) {
    if (!content || content.getAttribute('data-vigieau-enhanced') === '1') {
      return;
    }
    var originalHtml = content.innerHTML;
    var container = document.createElement('div');
    container.className = 'mesures-details-container';
    var hasDetails = false;

    toArray(content.children).forEach(function (child) {
      var tag = child.tagName ? child.tagName.toLowerCase() : '';
      if (tag === 'p') {
        var detail = buildDetailFromNode(child);
        if (detail) {
          container.appendChild(detail);
          hasDetails = true;
        }
      } else if (tag === 'ul' || tag === 'ol') {
        toArray(child.children).forEach(function (item) {
          var listDetail = buildDetailFromNode(item);
          if (listDetail) {
            container.appendChild(listDetail);
            hasDetails = true;
          }
        });
      }
    });

    if (hasDetails) {
      content.innerHTML = '';
      content.appendChild(container);
    } else {
      content.innerHTML = originalHtml;
      content.classList.add('mesures-content--plain');
    }

    content.setAttribute('data-vigieau-enhanced', '1');
  }

  function findTarget(widget, targetId) {
    if (!widget || !targetId) {
      return null;
    }
    try {
      return widget.querySelector('[id="' + targetId.replace(/"/g, '\\"') + '"]');
    } catch (e) {
      return document.getElementById(targetId);
    }
  }

  function initWidgetElement(widget) {
    if (!widget || widget.getAttribute('data-vigieau-ready') === '1') {
      return;
    }

    var toggles = toArray(widget.querySelectorAll('.mesures-toggle'));
    if (toggles.length === 0) {
      widget.setAttribute('data-vigieau-ready', '1');
      return;
    }

    toggles.forEach(function (button) {
      var targetId = button.getAttribute('data-target');
      var collapsedLabel = button.getAttribute('data-collapsed-label') || button.textContent || '';
      var expandedLabel = button.getAttribute('data-expanded-label') || collapsedLabel;
      var target = findTarget(widget, targetId);
      if (!target) {
        return;
      }

      enhanceContent(target);

      button.textContent = collapsedLabel;
      button.setAttribute('aria-expanded', 'false');
      button.dataset.collapsedLabel = collapsedLabel;
      button.dataset.expandedLabel = expandedLabel;

      target.classList.remove('is-expanded');
      target.setAttribute('aria-hidden', 'true');

      button.addEventListener('click', function (event) {
        event.preventDefault();
        var isExpanded = target.classList.toggle('is-expanded');
        target.setAttribute('aria-hidden', isExpanded ? 'false' : 'true');
        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        button.textContent = isExpanded ? expandedLabel : collapsedLabel;
        button.classList.toggle('is-open', isExpanded);
      });
    });

    widget.setAttribute('data-vigieau-ready', '1');
  }

  function initWidgetByUid(uid) {
    if (!uid) {
      return;
    }
    var widget = document.querySelector('.eqLogic[data-eqLogic_uid="' + uid + '"]');
    if (widget) {
      initWidgetElement(widget);
    }
  }

  window.vigieauInitMesuresWidget = function (uid) {
    initWidgetByUid(uid);
  };

})(window, document);

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
    var bodyIsText = false;
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
      bodyIsText = false;
    } else if (rawHtml !== '') {
      var split = rawHtml.split(/<br\s*\/?>/i);
      if (split.length > 1) {
        title = htmlToText(split.shift());
        bodyHtml = split.join('<br>').replace(/^(<br\s*\/?>)+/i, '').trim();
        bodyIsText = false;
      } else {
        title = htmlToText(rawHtml);
        bodyHtml = '';
        bodyIsText = false;
      }
    } else {
      title = normalizeSpace(clone.textContent || '');
      bodyIsText = false;
    }

    if (bodyHtml === '') {
      var textContent = normalizeSpace(clone.textContent || '');
      var colonIndex = textContent.indexOf(':');
      if (colonIndex !== -1 && colonIndex < textContent.length - 1) {
        var summaryText = normalizeSpace(textContent.substring(0, colonIndex));
        var detailText = normalizeSpace(textContent.substring(colonIndex + 1));
        if (summaryText !== '' && detailText !== '') {
          title = summaryText + ' :';
          bodyHtml = detailText;
          bodyIsText = true;
        }
      }
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

    var titleSpan = document.createElement('span');
    titleSpan.className = 'mesure-title';
    titleSpan.textContent = title;
    summary.appendChild(titleSpan);

    if (bodyHtml !== '') {
      var toggleHint = document.createElement('span');
      toggleHint.className = 'mesure-toggle-hint';
      toggleHint.textContent = '(plier/déplier)';
      summary.appendChild(toggleHint);
    }

    details.appendChild(summary);

    if (bodyHtml !== '') {
      var body = document.createElement('div');
      body.className = 'mesure-body';
      if (bodyIsText) {
        body.textContent = bodyHtml;
      } else {
        body.innerHTML = bodyHtml;
      }
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

  function setExpandedState(target, expanded) {
    if (!target) {
      return;
    }
    if (expanded) {
      target.classList.remove('is-collapsed');
      target.classList.add('is-expanded');
      target.setAttribute('aria-hidden', 'false');
    } else {
      target.classList.remove('is-expanded');
      target.classList.add('is-collapsed');
      target.setAttribute('aria-hidden', 'true');
    }
  }

  function initWidgetElement(widget) {
    if (!widget || widget.getAttribute('data-vigieau-ready') === '1') {
      return;
    }

    var contents = toArray(widget.querySelectorAll('.mesures-content'));
    contents.forEach(function (content) {
      enhanceContent(content);
    });

    var toggles = toArray(widget.querySelectorAll('.mesures-toggle'));
    if (toggles.length === 0) {
      contents.forEach(function (content) {
        setExpandedState(content, true);
      });
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

      button.textContent = collapsedLabel;
      button.setAttribute('aria-expanded', 'false');
      button.dataset.collapsedLabel = collapsedLabel;
      button.dataset.expandedLabel = expandedLabel;

      setExpandedState(target, false);

      button.addEventListener('click', function (event) {
        event.preventDefault();
        var isExpanded = !target.classList.contains('is-expanded');
        setExpandedState(target, isExpanded);
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

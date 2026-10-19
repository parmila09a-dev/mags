(function () {
  'use strict';

  function each(selector, callback, root) {
    Array.prototype.forEach.call((root || document).querySelectorAll(selector), callback);
  }

  /* Sign in: the login ID label follows the chosen role. */
  function initLogin() {
    var form = document.querySelector('[data-erp-login]');
    if (!form) {
      return;
    }

    var label = form.querySelector('[data-erp-login-label-target]');
    var hint = form.querySelector('[data-erp-login-hint-target]');
    var input = form.querySelector('#mags_erp_login');

    function sync() {
      var chosen = form.querySelector('input[name="mags_erp_as"]:checked');
      if (!chosen) {
        return;
      }
      label.textContent = chosen.getAttribute('data-erp-login-label');
      hint.textContent = chosen.getAttribute('data-erp-login-hint');
      input.setAttribute('autocapitalize', chosen.value === 'student' ? 'characters' : 'none');
    }

    each('input[name="mags_erp_as"]', function (radio) {
      radio.addEventListener('change', sync);
    }, form);
    sync();
  }

  function initPasswordToggles() {
    each('[data-erp-toggle-password]', function (button) {
      var input = document.getElementById(button.getAttribute('data-erp-toggle-password'));
      if (!input) {
        return;
      }
      button.hidden = false;
      button.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(show));
      });
    });
  }

  /* Ask before removing a record or replacing a password. */
  function initConfirms() {
    each('form[data-erp-confirm]', function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.confirm(form.getAttribute('data-erp-confirm'))) {
          event.preventDefault();
        }
      });
    });
  }

  function initCopy() {
    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      return;
    }
    each('[data-erp-copy]', function (button) {
      var original = button.textContent;
      button.hidden = false;
      button.addEventListener('click', function () {
        navigator.clipboard.writeText(button.getAttribute('data-erp-copy')).then(function () {
          button.textContent = 'Copied';
          window.setTimeout(function () {
            button.textContent = original;
          }, 2000);
        });
      });
    });
  }

  /* Class and date pickers load straight away; the Show button stays for browsers without JavaScript. */
  function initAutoSubmit() {
    each('form[data-erp-autosubmit]', function (form) {
      var button = form.querySelector('button[type="submit"]');
      if (button) {
        button.hidden = true;
      }
      form.addEventListener('change', function () {
        if (form.checkValidity()) {
          form.submit();
        }
      });
    });
  }

  /* Attendance sheet: live Present / Absent / Leave totals and "All present". */
  function initSheet() {
    var sheet = document.querySelector('[data-erp-sheet]');
    if (!sheet) {
      return;
    }

    var counts = sheet.querySelector('[data-erp-counts]');
    var allPresent = sheet.querySelector('[data-erp-all-present]');
    var saved = true;

    function update() {
      var totals = { P: 0, A: 0, L: 0 };
      each('input[type="radio"]:checked', function (radio) {
        if (totals.hasOwnProperty(radio.value)) {
          totals[radio.value] += 1;
        }
      }, sheet);
      counts.textContent = totals.P + ' present · ' + totals.A + ' absent · ' + totals.L + ' leave';
    }

    sheet.addEventListener('change', function () {
      saved = false;
      update();
    });

    if (allPresent) {
      allPresent.hidden = false;
      allPresent.addEventListener('click', function () {
        each('input[type="radio"][value="P"]', function (radio) {
          radio.checked = true;
        }, sheet);
        saved = false;
        update();
      });
    }

    sheet.addEventListener('submit', function () {
      saved = true;
    });
    window.addEventListener('beforeunload', function (event) {
      if (!saved) {
        event.preventDefault();
        event.returnValue = '';
      }
    });

    update();
  }

  /* One form, two answers (approve / do not approve): ask on the button that was pressed. */
  function initAsk() {
    each('button[data-erp-ask]', function (button) {
      button.addEventListener('click', function (event) {
        if (!window.confirm(button.getAttribute('data-erp-ask'))) {
          event.preventDefault();
        }
      });
    });
  }

  /* Long sheets (exam marks): warn before leaving with changes that are not saved. */
  function initGuard() {
    each('form[data-erp-guard]', function (form) {
      var saved = true;
      form.addEventListener('change', function () {
        saved = false;
      });
      form.addEventListener('submit', function () {
        saved = true;
      });
      window.addEventListener('beforeunload', function (event) {
        if (!saved) {
          event.preventDefault();
          event.returnValue = '';
        }
      });
    });
  }

  /* Marks sheet: an absent student has no marks to type. */
  function initAbsent() {
    each('.erp-mark__entry', function (entry) {
      var box = entry.querySelector('input[type="checkbox"]');
      var marks = entry.querySelector('input[type="text"]');
      if (!box || !marks || box.disabled) {
        return;
      }
      function sync() {
        marks.disabled = box.checked;
      }
      box.addEventListener('change', sync);
      sync();
    });
  }

  function initPrint() {
    if (!window.print) {
      return;
    }
    each('[data-erp-print]', function (button) {
      var inFlash = !!button.closest('.erp-flash');
      button.hidden = false;
      button.addEventListener('click', function () {
        document.body.classList.toggle('erp-print-flash', inFlash);
        window.print();
      });
    });
    window.addEventListener('afterprint', function () {
      document.body.classList.remove('erp-print-flash');
    });
  }

  /* Login details of students added from a file: save the table as a CSV file to keep. */
  function initSaveLogins() {
    var table = document.querySelector('[data-erp-logins] table');
    if (!table || !window.Blob || !window.URL || !window.URL.createObjectURL) {
      return;
    }
    function cell(text) {
      text = text.replace(/\s+/g, ' ').trim();
      if (/^[=+\-@]/.test(text)) {
        text = "'" + text;
      }
      return '"' + text.replace(/"/g, '""') + '"';
    }
    each('[data-erp-save-logins]', function (button) {
      button.hidden = false;
      button.addEventListener('click', function () {
        var lines = [];
        each('tr', function (row) {
          var cells = [];
          each('th, td', function (item) {
            cells.push(cell(item.textContent));
          }, row);
          lines.push(cells.join(','));
        }, table);
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(new Blob(['\ufeff' + lines.join('\r\n') + '\r\n'], { type: 'text/csv;charset=utf-8' }));
        link.download = button.getAttribute('data-erp-save-logins');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      });
    });
  }

  initLogin();
  initPasswordToggles();
  initConfirms();
  initAsk();
  initCopy();
  initAutoSubmit();
  initSheet();
  initGuard();
  initAbsent();
  initPrint();
  initSaveLogins();
}());

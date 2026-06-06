/**
 * Mobile FAB / bottom sheet for creating content.
 */
(function (Drupal, once) {
  Drupal.behaviors.mybgCreateFab = {
    attach(context) {
      once('mybg-create-fab', '[data-mybg-create-fab]', context).forEach((wrapper) => {
        const toggle = wrapper.querySelector('[data-mybg-create-fab-toggle]');
        const sheet = wrapper.querySelector('[data-mybg-create-sheet]');
        if (!toggle || !sheet) {
          return;
        }

        const closeTargets = sheet.querySelectorAll('[data-mybg-create-fab-close]');

        const openSheet = () => {
          sheet.hidden = false;
          toggle.setAttribute('aria-expanded', 'true');
          document.body.classList.add('mybg-create-sheet-open');
        };

        const closeSheet = () => {
          sheet.hidden = true;
          toggle.setAttribute('aria-expanded', 'false');
          document.body.classList.remove('mybg-create-sheet-open');
        };

        toggle.addEventListener('click', () => {
          if (sheet.hidden) {
            openSheet();
          } else {
            closeSheet();
          }
        });

        closeTargets.forEach((el) => el.addEventListener('click', closeSheet));

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && !sheet.hidden) {
            closeSheet();
          }
        });
      });
    },
  };
})(Drupal, once);

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.mybgOnboardingTour = {
    attach(context) {
      const settings = drupalSettings.mybgHelpTour;
      if (!settings || !settings.enabled) {
        return;
      }

      once('mybg-tour', 'body', context).forEach(() => {
        const steps = settings.steps.filter((s) => document.querySelector(s.target));
        if (!steps.length) {
          return;
        }

        let index = 0;
        let overlay;
        let popover;

        const finish = () => {
          overlay?.remove();
          popover?.remove();
          document.querySelectorAll('.mybg-tour-highlight').forEach((el) => {
            el.classList.remove('mybg-tour-highlight');
          });
          fetch(settings.completeUrl, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': settings.token,
            },
            credentials: 'same-origin',
          }).catch(() => {});
        };

        const showStep = (i) => {
          document.querySelectorAll('.mybg-tour-highlight').forEach((el) => {
            el.classList.remove('mybg-tour-highlight');
          });

          if (i >= steps.length) {
            finish();
            return;
          }

          const step = steps[i];
          const target = document.querySelector(step.target);
          if (!target) {
            showStep(i + 1);
            return;
          }

          target.classList.add('mybg-tour-highlight');
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });

          const rect = target.getBoundingClientRect();
          popover.style.top = `${Math.min(rect.bottom + 12 + window.scrollY, window.scrollY + window.innerHeight - 180)}px`;
          popover.style.left = `${Math.max(12, Math.min(rect.left + window.scrollX, window.innerWidth - 340))}px`;
          popover.querySelector('h3').textContent = step.title;
          popover.querySelector('p').textContent = step.text;
          popover.querySelector('.mybg-tour-next').textContent =
            i === steps.length - 1 ? 'Завершити' : 'Далі';
        };

        overlay = document.createElement('div');
        overlay.className = 'mybg-tour-overlay';
        document.body.appendChild(overlay);

        popover = document.createElement('div');
        popover.className = 'mybg-tour-popover';
        popover.innerHTML =
          '<h3></h3><p></p><div class="mybg-tour-popover__actions">' +
          '<button type="button" class="mybg-tour-skip">Пропустити</button>' +
          '<button type="button" class="mybg-tour-next">Далі</button></div>';
        document.body.appendChild(popover);

        popover.querySelector('.mybg-tour-skip').addEventListener('click', finish);
        popover.querySelector('.mybg-tour-next').addEventListener('click', () => {
          index += 1;
          showStep(index);
        });

        showStep(0);
      });
    },
  };
})(Drupal, drupalSettings, once);

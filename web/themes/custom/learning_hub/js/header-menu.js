(function (Drupal) {

  Drupal.behaviors.learningHubMenu = {
    attach: function (context) {

      const button = context.querySelector(
        '.learning-hub-menu-toggle'
      );

      const navigation = context.querySelector(
        '#learning-hub-navigation'
      );

      if (!button || !navigation) {
        return;
      }

      if (button.dataset.menuInitialized === 'true') {
        return;
      }

      button.dataset.menuInitialized = 'true';


      /*
       * Open / close when clicking the Menu button.
       */
      button.addEventListener('click', function (event) {

        event.stopPropagation();

        const isOpen = navigation.classList.contains(
          'learning-hub-navigation--open'
        );

        if (isOpen) {
          closeMenu();
        }
        else {
          openMenu();
        }

      });


      /*
       * Close menu when clicking anywhere outside it.
       */
      document.addEventListener('click', function (event) {

        const clickedInsideMenu = navigation.contains(
          event.target
        );

        const clickedMenuButton = button.contains(
          event.target
        );

        if (
          !clickedInsideMenu &&
          !clickedMenuButton
        ) {
          closeMenu();
        }

      });


      /*
       * Close menu when Escape key is pressed.
       */
      document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
          closeMenu();
        }

      });


      /*
       * Open menu.
       */
      function openMenu() {

        navigation.classList.add(
          'learning-hub-navigation--open'
        );

        button.setAttribute(
          'aria-expanded',
          'true'
        );

      }


      /*
       * Close menu.
       */
      function closeMenu() {

        navigation.classList.remove(
          'learning-hub-navigation--open'
        );

        button.setAttribute(
          'aria-expanded',
          'false'
        );

      }

    }
  };

})(Drupal);
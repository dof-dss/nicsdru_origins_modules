module.exports = {
  '@tags': ['origins', 'origins_workflow', 'debug'],

  before: function (browser) {
    // Resize default window size.
    browser.resizeWindow(1600, 2048);

    // Login as an editor.
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_editor', password: process.env.TEST_PASS })
      .drupalRelativeURL('/node/add/article');

    // Create a minimal article, populating mandatory fields only.
    // SHS widget isn't very friendly for form values so we need to simulate
    // actual click events on certain DOM elements.
    // '12' == Motoring theme.
    browser.click('@fieldSubtheme');
    browser.click('@fieldSubtheme option[value="12"]');
    browser
      .setValue('@title', 'Test article (draft to needs review)')
      .setValue('@fieldSummary', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
      .waitForElementVisible('@CKEditorBody', 2000)
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>'
        ]
      )
      .click('@submitForm');
  },

  'Check moderation task options': browser => {
    // Click to open the moderation sidebar.
    browser.click('@sidebarLink');

    // Check our sidebar label shows as 'draft' before we begin.
    browser.expect.element('@sidebarStatus').text.to.equal('Status: Draft');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('@sidebarTasks > a.button').text.to.equal('Edit content')
    browser.expect.element('@sidebarTasks #submit_for_review').to.have.attribute('value').equals('Submit for Review')
    browser.expect.element('@sidebarTasks #quick_publish').to.have.attribute('value').equals('Quick Publish')
    browser.expect.element('@sidebarTasks a.button--danger').text.to.equal('Delete content')
  },

  'DRAFT TO NEEDS REVIEW': browser => {
    // Moderation sidebar should already be open from previous test, click the button we need to change moderation state.
    browser.click('input#submit_for_review');

    // Check our sidebar status shows as 'needs review'.
    browser.click('@sidebarLink').expect.element('@sidebarStatus').text.to.equal('Status: Needs Review');

  }

};

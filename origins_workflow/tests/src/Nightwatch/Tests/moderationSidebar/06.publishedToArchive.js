module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  before: function (browser) {
    // Resize default window size.
    browser.resizeWindow(1600, 2048);
    // Dismiss EU cookie notice.
    browser.drupalRelativeURL('/')
      .pause(2000)
      .click('xpath', '//div[@id="popup-buttons"]//button[text()="Accept cookies"]');

    // Login as an editor.
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_editor', password: process.env.TEST_PASS })
      .drupalRelativeURL('/node/add/article');

    // Create a minimal article, populating mandatory fields only.
    // SHS widget isn't very friendly for form values so we need to simulate
    // actual click events on certain DOM elements.
    // '12' == Motoring theme.
    browser.click('select[id="edit-field-subtheme-shs-0-0"]');
    browser.click('select[id="edit-field-subtheme-shs-0-0"] option[value="12"]');
    browser.click('select[id="edit-moderation-state-0-state"] option[value="published"]');
    browser
      .setValue('input#edit-title-0-value', [Date.now() + ' -- ' + 'Test article (published to archive)', browser.Keys.TAB])
      .pause(2000)
      .setValue('textarea#edit-field-summary-0-value', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
      .waitForElementVisible('#cke_edit-body-0-value', 2000)
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>'
        ]
      )
      .click('input#edit-submit');
  },

  after: function (browser) {
    browser.drupalLogout();
  },

  'Check moderation task buttons': browser => {
    // Check our sidebar label shows as 'published' before we begin.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .expect.element('.moderation-sidebar-info > p:nth-child(1)').text.to.equal('Status: Published');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('.moderation-sidebar-primary-tasks > a.button').text.to.equal('Edit content')
    browser.expect.element('.moderation-sidebar-primary-tasks #archive').to.have.attribute('value').equals('Archive')
    browser.expect.element('.moderation-sidebar-primary-tasks #draft_of_published').to.have.attribute('value').equals('Draft of Published');
    browser.expect.element('.moderation-sidebar-primary-tasks a.button--danger').text.to.equal('Delete content')
  },

  'PUBLISHED TO ARCHIVE': browser => {
    // Moderation sidebar should already be open from previous test, click the button we need to change moderation state.
    browser.click('input#archive').acceptAlert();
    // After alert accept we should be redirected to the article page itself.

    // Check our sidebar label shows as 'archived'.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .expect.element('.moderation-sidebar-info > p:nth-child(1)').text.to.equal('Status: Archived');

  }

};

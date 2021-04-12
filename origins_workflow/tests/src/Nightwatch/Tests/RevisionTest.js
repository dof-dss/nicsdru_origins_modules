module.exports = {
  '@tags': ['origins', 'origins_workflow', 'debug'],

  before: function (browser) {
    // Resize default window size to aid with debugging, if needed.
    browser.resizeWindow(1600, 2048);
  },

  'Test login': browser => {
    const lipsumText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut " +
      "labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco " +
      "laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in " +
      "voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat " +
      "cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_admin', password: process.env.TEST_PASS });

    // Create a new published article for this test.
    browser.drupalRelativeURL('/node/add/article');
    browser.click('select[id="edit-field-subtheme-shs-0-0"]');
    browser.click('select[id="edit-field-subtheme-shs-0-0"] option[value="12"]');
    browser.click('select[id="edit-moderation-state-0-state"] option[value="published"]');
    browser
      .setValue('input#edit-title-0-value', [Date.now() + ' -- ' + 'RevisionTest.js test article', browser.Keys.TAB])
      .pause(2000)
      .setValue('textarea#edit-field-summary-0-value', lipsumText)
      .waitForElementVisible('#cke_edit-body-0-value', 2000)
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p> + lipsumText + </p>'
        ]
      )
      .click('input#edit-submit');

    // Edit the node and add a new draft.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a');
    browser.click('.moderation-sidebar-primary-tasks > a');
    browser.setValue('textarea#edit-field-summary-0-value', 'Draft edit');
    browser.click('input#edit-submit');

    // Back to the moderation sidebar to click the revisions tab.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a');
    browser.click('.moderation-sidebar-container a[href*="/revisions"]');

    // So we can then start our assertions by looking for specific bits of text.
    browser
      .expect.element('h1.page-title')
      .text.to.contain('Revisions for');

    /* Test that link text has been overridden with
    * 'Create Draft of Published' by origins_workflow_preprocess_table() */
    browser
      .useXpath()
      .expect.element('//table//tr[2]//td[5]//a')
      .text.to.contain('Create Draft of Published');
  }
};

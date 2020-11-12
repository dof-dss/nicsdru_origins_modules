module.exports = {
  '@tags': ['origins', 'origins_workflow', 'debug'],

  before: function (browser) {
    // Resize default window size.
    browser.resizeWindow(1600, 2048);

    // Dismiss EU cookie notice.
    browser.drupalRelativeURL('/')
      .pause(2000)
      .click('xpath', '//div[@id="popup-buttons"]//button[text()="Accept all cookies"]');

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
      .setValue('input#edit-title-0-value', [Date.now() + ' -- ' + 'Test article (ensure reverted drafts do not retain publish state)', browser.Keys.TAB])
      .pause(2000)
      .setValue('textarea#edit-field-summary-0-value', 'Some sample summary field text.')
      .waitForElementVisible('#cke_edit-body-0-value', 2000)
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p>Some initial body field content.</p>'
        ]
      )
      .click('input#edit-submit');
  },

  after: function (browser) {
    browser.drupalLogout();
  },

  'Check moderation sidebar options': browser => {
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a');
    browser.expect.element('.moderation-sidebar-info > p:nth-child(1)').text.to.equal('Status: Published');
  },

  'CREATE NEW DRAFT (R2)': browser => {
    browser.useXpath();

    // Moderation sidebar should already be open from previous test, click the button we need to change moderation state.
    browser.click("//*[@class=\"moderation-sidebar-primary-tasks\"]//a[text()='Edit content']");
    browser.useCss();
    browser
      .click('select[id="edit-moderation-state-0-state"] option[value="draft"]')
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p>Second revision (R2), draft.</p>'
        ]
      )
      .click('input#edit-submit');

    // Check our sidebar label shows as 'Draft'.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .expect.element('.moderation-sidebar-info > p:nth-child(1)').text.to.equal('Status: Draft');
  },

  'CHECK LIVE CONTENT': browser => {
    // Click the 'View live content' button.
    browser.useXpath();
    browser.click("//*[@class=\"moderation-sidebar-toolbar-tab toolbar-tab\"]");
    browser.click("//*[@class=\"moderation-sidebar-primary-tasks\"]//a[text()='View live content']");

    // Confirm we have a 'Draft available moderation label'.
    browser.click('//*[@class="moderation-sidebar-toolbar-tab toolbar-tab"]//a')
      .expect.element('//*[@class="moderation-sidebar-toolbar-tab toolbar-tab"]//a')
      .to.have.attribute('data-label')
      .equals('Draft available');
  }

};

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
      .setValue('input#edit-title-0-value', [Date.now() + ' -- ' + 'Test article (published to draft of published)', browser.Keys.TAB])
      .pause(2000)
      .setValue('textarea#edit-field-summary-0-value', 'Lorem ipsum dolor sit amet.')
      .waitForElementVisible('#cke_edit-body-0-value', 2000)
      .execute(function (instance, content) {
          CKEDITOR.instances[instance].setData(content);
        }, [
          'edit-body-0-value',
          '<p>Lorem ipsum dolor sit amet</p>'
        ]
      )
      .click('input#edit-submit');

    // Set up a forward revision.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .click('div.moderation-sidebar-primary-tasks > a:nth-child(1)')
      .pause(2000)
      .setValue('textarea#edit-field-summary-0-value', 'A forward revision change')
      .click('input#edit-submit');

    // View the published revision to see the draft of published link option.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .click('div.moderation-sidebar-primary-tasks > a:nth-child(1)');
  },

  after: function (browser) {
    browser.drupalLogout();
  },

  'DRAFT OF PUBLISHED WITH FORWARD REVISION': browser => {
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a')
      .click('div.moderation-sidebar-primary-tasks > a.draft-of-published--link');
    browser.click('input#edit-submit');

    // Validate remaining sidebar options.
    browser.click('div.moderation-sidebar-toolbar-tab.toolbar-tab > a');
    browser.expect.element('div.moderation-sidebar-primary-tasks > a:nth-child(1)').text.to.equal('View existing draft');
    browser.expect.element('div.moderation-sidebar-primary-tasks > a:nth-child(2)').text.to.equal('Edit draft');
    browser.expect.element('div.moderation-sidebar-primary-tasks > a:nth-child(3)').text.to.equal('Draft of published');
    browser.expect.element('div.moderation-sidebar-primary-tasks > a:nth-child(4)').text.to.equal('Archive');
    browser.expect.element('div.moderation-sidebar-primary-tasks > a:nth-child(5)').text.to.equal('Delete content');

  }

};

let editorModerationSidebarButtons = {
  'draft': [
    'Edit content',
    'Submit for Review',
    'Quick Publish',
  ],
  'needs_review': [
    'Edit content',
    'Reject',
    'Publish',
  ],
  'published': [
    'Edit content',
    'Unpublish',
    'Archive',
    'Draft of Published',
  ]
};

module.exports = {
  '@tags': ['origins', 'origins_workflow', 'debug'],

  'CREATE DRAFT: create new content + check general moderation sidebar status': browser => {

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
    browser.click('#edit-field-subtheme-shs-0-0');
    browser.click('#edit-field-subtheme-shs-0-0 option[value="12"]');
    browser
      .setValue('input#edit-title-0-value', 'Editor created test article')
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

    // Will have redirected to the node view page, check the output of the sidebar element.
    // Should be RED background with 'DRAFT' label.
    // browser.expect.element('#toolbar-bar .moderation-sidebar-toolbar-tab a.toolbar-icon-moderation-sidebar:after')
    //   .to.have.css('background', '#bd2727');
    browser.expect.element('#toolbar-bar .moderation-sidebar-toolbar-tab a.toolbar-icon-moderation-sidebar')
      .to.have.attribute('data-label', 'Draft');

  },

  'DRAFT: check moderation sidebar options': browser => {
    // Should already be at the article page. Click to open the moderation sidebar.
    browser.click('#toolbar-bar > div.moderation-sidebar-toolbar-tab.toolbar-tab > a');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('.moderation-sidebar-primary-tasks > a.button').text.to.equal('Edit content')
    browser.expect.element('.moderation-sidebar-primary-tasks #submit_for_review').to.have.attribute('value').equals('Submit for Review')
    browser.expect.element('.moderation-sidebar-primary-tasks #quick_publish').to.have.attribute('value').equals('Quick Publish')
    browser.expect.element('.moderation-sidebar-primary-tasks a.button--danger').text.to.equal('Delete content')

    // Click to progress to next workflow state: needs review.
    browser.click('input#submit_for_review');
  },

  'DRAFT >> NEEDS REVIEW: check moderation sidebar options': browser => {
    // Should already be at the article page. Click to open the moderation sidebar.
    browser.click('#toolbar-bar > div.moderation-sidebar-toolbar-tab.toolbar-tab > a');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('.moderation-sidebar-primary-tasks > a.button').text.to.equal('Edit content')
    browser.expect.element('.moderation-sidebar-primary-tasks #reject').to.have.attribute('value').equals('Reject')
    browser.expect.element('.moderation-sidebar-primary-tasks #publish').to.have.attribute('value').equals('Publish')
    browser.expect.element('.moderation-sidebar-primary-tasks a.button--danger').text.to.equal('Delete content')
  },

  'NEEDS REVIEW >> PUBLISH: check moderation sidebar options': browser => {
    browser.expect.element('.moderation-sidebar-toolbar-tab a.toolbar-icon-moderation-sidebar')
      .to.have.attribute('data-label', 'Published');

    // Should already be at the article page. Click to open the moderation sidebar.
    browser.click('#toolbar-bar > div.moderation-sidebar-toolbar-tab.toolbar-tab > a');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('.moderation-sidebar-primary-tasks > a.button').text.to.equal('Edit content')
    browser.expect.element('.moderation-sidebar-primary-tasks #reject').to.have.attribute('value').equals('Unpublish')
    browser.expect.element('.moderation-sidebar-primary-tasks #publish').to.have.attribute('value').equals('Archive')
    browser.expect.element('.moderation-sidebar-primary-tasks a.button--danger').text.to.equal('Draft of Published');

  },

  'PUBLISH >> ARCHIVE: check moderation sidebar options': browser => {
    // Should already be at the article page. Click to open the moderation sidebar.
    browser.click('#toolbar-bar > div.moderation-sidebar-toolbar-tab.toolbar-tab > a');
    // Click to progress to next workflow state: publish
    browser.click('input#archive').acceptAlert();

    browser.expect.element('.moderation-sidebar-toolbar-tab a.toolbar-icon-moderation-sidebar')
      .to.have.attribute('data-label', 'Archived');

    // DOM is a hybrid of styled links and form enclosed, styled input elements which makes
    // iterating over the collection rather more complex than desired. Everything here is very
    // precise with selectors to compensate as a result.
    browser.expect.element('.moderation-sidebar-primary-tasks > a.button').text.to.equal('Edit content')
    browser.expect.element('.moderation-sidebar-primary-tasks #unpublish').to.have.attribute('value').equals('Unpublish')
    browser.expect.element('.moderation-sidebar-primary-tasks #archive').to.have.attribute('value').equals('Archive')
    browser.expect.element('.moderation-sidebar-primary-tasks #draft_of_published').to.have.attribute('value').equals('Draft of Published')
    browser.expect.element('.moderation-sidebar-primary-tasks a.button--danger').text.to.equal('Delete content');

  }

};

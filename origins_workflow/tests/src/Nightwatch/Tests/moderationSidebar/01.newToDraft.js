module.exports = {
  '@tags': ['origins', 'origins_workflow', 'debug'],

  before: function (browser) {
    // Resize default window size.
    browser.resizeWindow(1600, 2048);
  },

  'CREATE NEW DRAFT': browser => {
    // Login as an editor.
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_editor', password: process.env.TEST_PASS })
      .drupalRelativeURL('/node/add/article');

    // Load page objects.
    const pageObjects = browser.page.ModerationSidebar();

    pageObjects.createArticleNode('draft');
    pageObjects.checkModerationStatus('Status: Draft');
  }

};

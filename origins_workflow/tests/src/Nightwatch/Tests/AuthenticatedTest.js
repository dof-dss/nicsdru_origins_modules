module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_authenticated', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/admin/site_themes')
      .expect.element('h1.page-title')
      .text.to.contain('Site Themes');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Access denied');

    browser
      .drupalRelativeURL('/admin/workflow/drafts')
      .expect.element('h1.page-title')
      .text.to.contain('Access denied');

    browser
      .drupalRelativeURL('/admin/workflow/all-drafts')
      .expect.element('h1.page-title')
      .text.to.contain('Access denied');

    browser
      .drupalRelativeURL('/admin/workflow/needs-review')
      .expect.element('h1.page-title')
      .text.to.contain('Access denied');

    browser
      .drupalRelativeURL('/admin/workflow/needs-audit')
      .expect.element('h1.page-title')
      .text.to.contain('Access denied');

    // TODO Test access to D8 equivalent of 'File list' option
  }

};

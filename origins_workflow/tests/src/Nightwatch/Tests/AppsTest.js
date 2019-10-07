module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: 'nw_test_apps', password: process.env.TEST_PASS });

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
  }

};

module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: 'nw_test_editor', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Add content');

    browser
      .drupalRelativeURL('/admin/workflow/drafts')
      .expect.element('h1.page-title')
      .text.to.contain('My Drafts');

    browser
      .drupalRelativeURL('/admin/workflow/all-drafts')
      .expect.element('h1.page-title')
      .text.to.contain('All drafts');

    browser
      .drupalRelativeURL('/admin/workflow/needs-review')
      .expect.element('h1.page-title')
      .text.to.contain('Needs Review');

    browser
      .drupalRelativeURL('/admin/workflow/needs-audit')
      .expect.element('h1.page-title')
      .text.to.contain('Needs Audit');
  }

};

module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: 'nw_test_gp_author', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/admin/workflow/drafts')
      .expect.element('h1.page-title')
      .text.to.contain('My Drafts');

    browser
      .drupalRelativeURL('/admin/workflow/needs-review')
      .expect.element('h1.page-title')
      .text.to.contain('Needs Review');
  }

};

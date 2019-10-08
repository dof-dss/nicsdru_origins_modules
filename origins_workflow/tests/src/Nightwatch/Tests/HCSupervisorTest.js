module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: 'nw_test_hc_super', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/admin/structure/taxonomy_manager/voc')
      .expect.element('h1.page-title')
      .text.to.contain('Taxonomy Manager');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Create Health condition');

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

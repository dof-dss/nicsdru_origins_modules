module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_driving_super', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Create Driving instructor');

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

    // TODO Test access to D8 equivalent of 'File list' option
  }

};

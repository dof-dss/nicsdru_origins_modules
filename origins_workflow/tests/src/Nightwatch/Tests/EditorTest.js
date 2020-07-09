module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_editor', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Add content');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Article');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.not.contain('Driving instructor');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Landing page');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.not.contain('Recipe');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.not.contain('Webform');

    browser
      .drupalRelativeURL('/admin/content/drafts')
      .expect.element('h1.page-title')
      .text.to.contain('My Drafts');

    browser
      .drupalRelativeURL('/admin/content/all-drafts')
      .expect.element('h1.page-title')
      .text.to.contain('All drafts');

    browser
      .drupalRelativeURL('/admin/content/needs-review')
      .expect.element('h1.page-title')
      .text.to.contain('Needs Review');

    browser
      .drupalRelativeURL('/admin/content/needs-audit')
      .expect.element('h1.page-title')
      .text.to.contain('Needs Audit');

    // TODO Test access to D8 equivalent of 'File list' option
  }

};

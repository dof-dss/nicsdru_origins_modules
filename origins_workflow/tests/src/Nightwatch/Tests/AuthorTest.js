module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_author', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('h1.page-title')
      .text.to.contain('Add content');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Application');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Article');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Contact');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Embargoed publication');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('External link');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Link');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('News');

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('ul.admin-list')
      .text.to.contain('Publication');

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
      .text.to.contain('Access denied');

  }

};

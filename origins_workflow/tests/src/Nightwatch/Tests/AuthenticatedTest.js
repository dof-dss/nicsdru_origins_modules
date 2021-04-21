module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_authenticated', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/add')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content/media')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content/drafts')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content/all-drafts')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content/needs-review')
      .expect.element('body')
      .text.to.contain('Page not found');

    browser
      .drupalRelativeURL('/admin/content/needs-audit')
      .expect.element('body')
      .text.to.contain('Page not found');

  }

};

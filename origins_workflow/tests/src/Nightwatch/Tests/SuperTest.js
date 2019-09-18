module.exports = {
  '@tags': ['core'],

  before(browser) {
    browser.drupalInstall();
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Test login': browser => {
    browser
      .drupalCreateUser({
        name: 'night_supervisor',
        password: 'password',
        permissions: ['view the administration theme', 'access administration pages'],
      })
      .drupalLogin({ name: 'night_supervisor', password: 'password' })
      .drupalRelativeURL('/admin/content')
      .expect.element('h1.page-title')
      .text.to.contain('Content');
  },
};

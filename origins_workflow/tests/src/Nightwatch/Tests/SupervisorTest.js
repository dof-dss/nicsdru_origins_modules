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
        permissions: ['access content'],
      })
      .drupalLogin({ name: 'night_supervisor', password: 'password' })
      .drupalRelativeURL('/admin/reports')
      .expect.element('h1.page-title')
      .text.to.contain('Reports');
  },
};

module.exports = {
  '@tags': ['nidirect-migrations', 'nidirect-files'],

  'Creates a supervisor': browser => {
    browser
      .drupalLogin({ name: process.env.TEST_USER, password: process.env.TEST_PASS });

    browser
      .drupalCreateUser({
        name: 'night_supervisor',
        password: 'password',
        permissions: ['access content'],
      });

    browser.drupalLogout();

    browser
      .drupalLogin({ name: 'night_supervisor', password: 'password' });

  }
};

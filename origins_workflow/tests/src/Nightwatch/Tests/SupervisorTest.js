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

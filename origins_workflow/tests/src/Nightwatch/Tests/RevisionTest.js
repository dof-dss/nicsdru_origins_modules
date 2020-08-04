module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_admin', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/4807/revisions')
      .expect.element('h1.page-title')
      .text.to.contain('Revisions for');

    browser
      .drupalRelativeURL('/node/add/article')
      .assert.titleContains('Create Article')
      .assert.visible('#edit-title-0-value')
      .setValue('#edit-title-0-value', 'test title')
      .assert.visible('#edit-field-subtheme-shs-0-0')
      .click('#edit-field-subtheme-shs-0-0 option[value="12"]')
      .assert.visible('#edit-submit');

  }

};

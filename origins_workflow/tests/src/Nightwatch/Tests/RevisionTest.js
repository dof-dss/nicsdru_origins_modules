module.exports = {
  '@tags': ['origins', 'origins_workflow'],

  'Test login': browser => {
    browser
      .drupalLogin({ name: process.env.NW_TEST_USER_PREFIX + '_admin', password: process.env.TEST_PASS });

    browser
      .drupalRelativeURL('/node/698/revisions')
      .expect.element('h1.page-title')
      .text.to.contain('Revisions for');

    /* Test that link text has been overridden with
    * 'Create Draft of Published' by origins_workflow_preprocess_table() */
    browser
      .useXpath()
      .expect.element('//table//tr[2]//td[5]//a')
      .text.to.contain('Create Draft of Published');
  }
};

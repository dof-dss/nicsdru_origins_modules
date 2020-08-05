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
      .useXpath()
      .expect.element('//table//td[5]//em')
      .text.to.contain('Current revision');

    browser
      .useXpath()
      .expect.element('//table//tr[3]//td[5]//div//div//ul//li//a')
      .text.to.contain('Copy to new revision');
  }
};

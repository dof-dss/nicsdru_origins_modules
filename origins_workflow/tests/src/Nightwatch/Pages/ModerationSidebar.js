module.exports = {
  elements: {
    title: {
      selector: 'input#edit-title-0-value',
    },
    fieldSummary: {
      selector: 'textarea#edit-field-summary-0-value',
    },
    fieldSubThemeWithMotoringValue: {
      selector: 'select[id="edit-field-subtheme-shs-0-0"] option[value="12"]',
    },
    ckEditorBody: {
      selector: '#cke_edit-body-0-value',
    },
    sidebarLink: {
      selector: 'div.moderation-sidebar-toolbar-tab.toolbar-tab > a',
    },
    sidebarTasks: {
      selector: '.moderation-sidebar-primary-tasks',
    },
    sidebarStatus: {
      selector: '.moderation-sidebar-info > p:nth-child(1)',
    },
  },
  props: {
    titleText: 'Test article',
    loremIpsum: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    sidebarButtons: {
      'draft': [
        'Edit content',
        'Submit for Review',
        'Quick Publish',
        'Delete content',
      ],
    },
  },
  commands: [{
    // Some selectors using raw CSS values because we need them to be dynamic
    // and the element aliases options don't allow us to interpolate the values.
    // See fieldSubThemeWithMotoringValue vs moderation state dropdown below as examples.
    createArticleNode: function(workflowState) {
      return this
        .setValue('@title', this.props.titleText + ' (' + workflowState + ')')
        .setValue('@fieldSummary', this.props.loremIpsum)
        .click('@fieldSubThemeWithMotoringValue')
        .waitForElementVisible('@ckEditorBody', 2000)
        .api.execute(function (instance, content) {
            CKEDITOR.instances[instance].setData(content);
          }, [
            'edit-body-0-value',
            this.props.loremIpsum
          ]
        )
        .click('select[id="edit-moderation-state-0-state"] option[value="' + workflowState + '"]')
        .click('input#edit-submit');
    },
    checkModerationStatus: function(text) {
      return this
        .click('@sidebarLink').expect.element('@sidebarStatus').text.to.equal(text);
    },
    sidebarButtonClick: function(value) {
      return this.click(value);
    },
  }]
};

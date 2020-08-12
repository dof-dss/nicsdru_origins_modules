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
      selector: '#toolbar-bar > div.moderation-sidebar-toolbar-tab.toolbar-tab > a',
    },
    sidebarTasks: {
      selector: '.moderation-sidebar-primary-tasks',
    },
    sidebarStatus: {
      selector: '.moderation-sidebar-info > p:nth-child(1)',
    },
    moderationStateOptions: {
      selector: '#edit-moderation-state-0-state',
    },
    articleNodeForm: {
      selector: 'input#edit-submit',
    }
  },
  props: {
    titleText: 'Test article',
    loremIpsum: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
  },
  commands: [{
    createDraftArticleNode: function() {
      return this
        .setValue('@title', this.props.titleText + ' (new to draft)')
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
        .click('input#edit-submit');
    },
    checkModerationStatus: function(text) {
      return this
        .click('@sidebarLink').expect.element('@sidebarStatus').text.to.equal(text);
    }
  }]
};

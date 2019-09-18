/**
 * Logs into Drupal as the given user.
 *
 * @param {object} settings
 *   Settings object
 * @param {string} settings.name
 *   The user name.
 * @param {string} settings.password
 *   The user password.
 * @param {array} [settings.permissions=[]]
 *   The list of permissions granted for the user.
 * @param {function} callback
 *   A callback which will be called, when the creating the use is finished.
 * @return {object}
 *   The drupalCreateUser command.
 */
exports.command = function drupalCreateUserWithRoles(
  { name, password, roles = [] },
  callback,
) {
  const self = this;
  this.drupalLoginAsAdmin(() => {
    this.drupalRelativeURL('/admin/people/create')
      .setValue('input[name="name"]', name)
      .setValue('input[name="pass[pass1]"]', password)
      .setValue('input[name="pass[pass2]"]', password)
      .perform((client, done) => {
        client.click(`input[name="roles[supervisor_user]`, () => {
          done();
        });
      })
      .submitForm('#user-register-form')
      .assert.containsText(
        '.messages',
        'Created a new user account',
        `User "${name}" was created succesfully.`,
      );
  });

  if (typeof callback === 'function') {
    callback.call(self);
  }

  return this;
};

# Origins Workflow Module

# Workflow

If installing this module in a new site, head to the /admin/config/workflow/workflows admin page. Here you should delete
the default 'Editorial' workflow as we will be using the 'NICS Editorial Workflow' that is installed with this module.

Edit the workflow by clicking 'Edit'. The 'states' and 'transitions' should be OK for your site, but you should make sure
that you edit the 'Content types' in the 'This Workflow Applies To' section and select the content types that you wish
workflow to apply to.

# Auditing

Auditing is the process of automatically flagging content to be audited 6 months after publication.
The first step is to select the content types that you wish auditing to apply to at /admin/config/origins_workflow/auditsettings.
Once this has been done, you will see that the 'field_next_audit_due' field has been added to the selected content types.
When you publish a new node of an 'auditable' content type, the 'origins_workflow_entity_presave' function will automatically set
an audit date six months in the future.
Later, you can search for nodes on your site that are due for audit at /admin/workflow/needs-audit.
When you view a node that is due for audit, you will see an 'Audit this published content' link. If you (or someone with
appropriate permissions) clicks on this link, then there will be a further 'Audit this published content' link - if you click
this as well then the auditing process is completed and the audit date is set a further six months in the future.

N.B. If developers wish to test auditing by setting audit dates manually, this can be done in one of two ways:
- by temporarily commenting out this line in origins_workflow_form_alter
$form['field_next_audit_due']['#access'] = FALSE;
- by manually setting audit dates in the database in the node__field_next_audit_due table

# Testing

Note that some functional and Nightwatch tests have been included with this module.
For the Nightwatch tests to work, however, two environment variables must be set:

NW_TEST_USER_PREFIX  (a prefix for the test user names, so that user names are not viewable in source code)
TEST_PASS           (a password for the test users)

If you are using Lando, these environment variables may be set in config/local.envvars and the test may be run as follows:

lando nightwatch ../modules/origins/origins_workflow/tests/src/Nightwatch/Tests/AdminTest.js

(Note that this is just an example test, there are others available in that directory)

# Workflow Views and local overrides.

The Origins Workflow module includes the 'Workflow: Moderation' View which will be enabled when the module is installed.
This View provides several displays for the Origins Moderation workflow such as 'All Drafts', 'My Drafts', 'Archived' etc.
and provides a common editorial overview without the need to manually re-create the same View for each site.
The View can be altered by anyone with 'Administer Views' permission and exported with these changes as part of your site configuration.
The problem with this is that when the Origins workflow View is updated any local changes to the View will be overwritten.

To work around this issue we introduced local overrides and this is available at Configuration => Origins => Moderation settings.
The settings applied here are layered on top of the configuration loaded from the config storage.

These override options are hard coded and if you require any additional options take a look at:
- src/Form/ModerationSettingsForm.php
- src/Config/WorkflowOverride.php

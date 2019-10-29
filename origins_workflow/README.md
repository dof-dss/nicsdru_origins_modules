# Origins Workflow Module

Note that some functional and Nightwatch tests have been included with this module.

For the Nightwatch tests to work, however, two environment variables must be set:

NW_TEST_USER_PREFIX  (a prefix for the test user names, so that user names are not viewable in source code)
TEST_PASS           (a password for the test users)

If you are using Lando, these environment variables may be set in config/local.envvars and the test may be run as follows:

lando nightwatch ../modules/origins/origins_workflow/tests/src/Nightwatch/Tests/AdminTest.js

(Note that this is just an example test, there are others available in that directory)

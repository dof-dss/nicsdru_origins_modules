# Origins QA


### Creating QA staff accounts

To automatically create accounts for QA staff members the following steps are required:

Create:
- Environment variable called QA_STAFF_ACCOUNTS with a string of comma separated email addresses.
- Environment variable called QA_STAFF_ROLE with the string of a role present on the site that you want to assign to the QA staff accounts.
- Post deploy hook operation to run the command `drush create_qa_staff_accounts`.

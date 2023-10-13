# Web App Account Creation and Login

## Database 

The database has a table for user accounts with these fields:
- ID
- CreateDate
- Email
- HashedPassword (300 chars)
- ConfirmationKey
- ConfirmationDate
- IsActive
- PasswordResetRequestDate
- PasswordResetKey
- PasswordResetDate

This table can have a foreign key relationship with other tables holding data associated with each account.

## Data Rules

### Email rules
When an email input is used in a form
- type = email
- Input ID is “username”.

There is some basic email validation: not blank, follows an acceptable pattern.

### Password rules
When a password input is used in a form
- type=password
- autocomplete = false
- Input ID is “password”.

Use zxcvbn-ts to show password strength to the user. 

Save the zxcvbn-ts score in a hidden input.

When a new password is submitted in a form, it is validated in this way:
- Minimum 8 characters, maximum 64 characters
- Do not accept a zxcvbn-ts score of 0 or 1.

HashedPassword is saved with Argon2 encryption.


## Web app behavior

### Create an account

The form has three inputs, Email and Password, PasswordScore.

Upon submitting, ID, CreateDate, Email, HashedPassword, ConfirmationKey are saved to the database. Any existing unconfirmed accounts with the same email address are deleted in the database. 

A line is added to the log: `{"event": "user_created:anonymous,[Email],unconfirmed_applicant", "level": "INFO", "description": "[Email] created an account."}`

An email confirmation is sent to the email address with a link containing ConfirmationKey to confirm the address. 

The user sees this response: "A link to activate your account has been emailed to the address provided."

### Confirm the account

Upon clicking the link, the ConfirmationKey is saved to session.

If the user arrives with a bad link (e.g, the key doesn't exist in the database), the user sees a generic error message. A line is added to the log: `{"event": "authn_login_fail:anonymous", "level": "WARN", "description": "Anonymous user at [IP Address] attempted to access the confirm-account page with a bad key"}`

If the user arrives with an old link (e.g., after 24 hours), the user sees a generic error message. A line is added to the log: `{"event": "authn_login_fail:anonymous", "level": "INFO", "description": "Anonymous user at [IP Address] attempted to access the confirm-account page with a expired key"}`

If the user arrives with a good link (e.g, the key exists in the database), the user sees a generic error message. A line is added to the log: `{"event": "authn_login_success:anonymous", "level": "INFO", "description": "Anonymous user accessed the confirm-account with a good key"}`

There is a form with two inputs: Email and Password. The validation is:
- The confirmation key in session matches this email address's key in the database.

Upon submitting, ConfirmationDate and IsActive are saved.  A line is added to the log: `{"event": "authz_change:[Email],unconfirmed_applicant,confirmed_applicant", "level": "INFO", "description": "[Email] confirmed their account."}` Now any other data in other tables can be associated with this account.

An email confirmation is sent to the email address saying the account has been activated. A line is added to the log: `{"event": "email_sent:[Email]", "level": "INFO", "description": "[Email] was sent the ‘[Subject]’ email.”}`

### Log in

The form has two inputs: Email and Password. Validation here is:
- Password is 64 chars max
- Both email and password are supplied.

A user can only log in to an account where IsActive is true.

Upon submitting, the user is either redirected to an appropriate page that indicates success or see this message: "Login failed; Invalid user ID or password."

A line is added to the log in case of successful login: `{"event": "authn_login_success:[Email]", "level": "INFO", "description": "User [Email] login successful"}`

A line is added to the log in case of unsuccessful login: `{"event": "authn_login_fail:[Email]", "level": "WARN", "description": "User [Email] login failed"}`

### Request resetting the password

The form has one input: Email. 

Upon submitting, PasswordResetRequestDate and PasswordResetKey are both set. An email is sent to the email address with the key, with instructions to click the link and change the password or ignore the email. The account is still active so a user can still log in.

A line is added to the log: `{"event": "user_updated:[Email],[Email],password_reset", "level": "WARN", "description": "User [Email] requested a password reset key."}`

### Reset the password

Upon clicking the email link, the PasswordResetKey is saved to session.

If the user arrives with a bad link (e.g, the key doesn’t exist in the database), the user sees a generic error message. A line is added to the log: `{"event": "authn_login_fail:anonymous", "level": "WARN", "description": "Anonymous user at [IP Address] attempted to access the reset-password page with a bad key"}`

If the user arrives 15 minutes or more after PasswordResetRequestDate, the user sees a generic error message. A line is added to the log: `{"event": "authn_login_fail:anonymous", "level": "WARN", "description": "Anonymous user at [IP Address] attempted to access the reset-password page after the request expiration"}`

If the user arrives with a good link (e.g, the key exists in the database), the user sees a generic error message. A line is added to the log: `{"event": "authn_login_success:anonymous", "level": "INFO", "description": "Anonymous user accessed the reset-password page with a good key"}`

There is a form with two inputs: Email and Password. The validation includes:
- The reset-password key in session matches this email address’s key in the database.

Upon submitting, PasswordResetDate and HashedPassword are saved. 

A line is added to the log in case of success: `{"event": "authn_password_change:[Email]", "level": "INFO", "description": "User [Email] successfully changed password"}`

A line is added to the log in case of failure: `{"event": "authn_password_change_fail:[Email]", "level": "INFO", "description": "User [Email] failed to change password"}`

## Email behavior

The email class logs all occurrences of sending email. That is, a line is added to the log: `{"event": "email_sent:[Email]", "level": "INFO", "description": "[Email] was sent the ‘[Subject]’ email."}`

## Logging behavior

App events will be logged to a CSV with these fields:
- Datetime: in ISO 8601 format WITH UTC offset, "2021-01-01T01:01:01-0700"
- Event (owasp nomenclature)
- Level
- Description
- Source IP: $_SERVER['HTTP_USER_AGENT']
- Host IP: $_SERVER['REMOTE_ADDR']
- Host Protocol: $_SERVER['REQUEST_SCHEME']
- Host Port: $_SERVER['SERVER_PORT']
- Request URI: $_SERVER['SCRIPT_FILENAME']
- Request Method: $_SERVER['REQUEST_METHOD']

The log will be saved periodically for audit purposes.

## References:

https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html

https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html

https://cheatsheetseries.owasp.org/cheatsheets/Logging_Vocabulary_Cheat_Sheet.html#authn_login_failuserid

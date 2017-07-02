# Mailgun Mailer for Expression Engine

This is a port of [MandrillMailer](https://github.com/tjdraper/MandrillMailer) for use with [Mailgun](https://mailgun.com).  You'll need to set up at least a free account and fetch your API key and sandbox domain.

## Configuration

Copy directory `mailgun_mailer` to `system/expression/third_party`.

Add the following to `system/expressionengine/config/config.php`:

```
$config['mailgun_key'] = 'key-XXXXXXXXXXXXXXXXX';
$config['mailgun_domain'] = 'sandboxXXXXXXXXXX.mailgun.org';
```

These parameters are available on the [domain configuration page](https://mailgun.com/app/domains). To send from a real domain, you'll need to verify ownership in Mailgun.

## Tag parameters

Quick example (borrowed from [MandrillMailer](https://buzzingpixel.com/ee-add-ons/mandrill-mailer/documentation/the-tag-pair))

```
{exp:mailgun_mailer:form
  allowed="phone|message"
  to="janedoe@internet.com"
  from="johndoe@internet.com"
  subject="Cool Stuff Happening"
  from-name="John Doe"
  required="phone|message"
  message="phone|message"
  return="/my/uri"
  json="yes"
  class="my-class"
  id="my-id"}

  {!-- your form here--don't include the <form> tag, e.g.: --}
    <input type="text" name="phone">
    <input type="text" name="message">

{/exp:mailgun_mailer}
```

## Additional features

### Email templates: `output_template`

By default, the mailer will produce a simple list of fields listed by form name.  For more control over email composition, you can provide the path to an EE-style template that will control the HTML output of the mailer.  For instance, tag parameter `output_template="helpers/email_template"` will look for a template within your template folder default_site/helpers.group.  Within that template file, input values are specified by simple tags. For example:

```
<strong>First name:</strong> {first_name}<br>
<strong>MI:</strong> {mi}<br>
<strong>Last name:</strong> {last_name}<br>
```

where `first_name`, `last_name`, and `mi` are input `name` attributes in the submitted form.

### Recaptcha

[Google's reCAPTCHA service](https://www.google.com/recaptcha/intro/index.html) is supported.  Set parameter `recaptcha="yes"` to enable the submission check and drop tag `{recaptcha}` somewhere inside your form to enable the widget.

### Honeypot field

An alterate or complementary spam detection technique is to provide a fake "honeypot" field that to human users should be hidden and left blank.  Bots may be detected because they may fill this field with arbitrary text.  Using `honeypot="test_email"` and then adding field `<input id="test_email" name="test_email">` within your form will check to make sure this input submission remains blank.  To make sure normal users don't fill in this field, make sure to hide it with styling:

```css
#test_email {
  display: none;
}
```

## Notes

Tracking is currently disabled in the plugin--this is a very simple sender.  PRs for expanded functionality are welcome.

For convenience, a snapshot of [mailgun-php](https://github.com/mailgun/mailgun-php) is bundled.
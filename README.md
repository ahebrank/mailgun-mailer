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
  id="my-id"
  attr:my-attribute="my-data"}

  {!-- your form here--don't include the <form> tag, e.g.: --}
    <input type="text" name="phone">
    <input type="text" name="message">

{/exp:mailgun_mailer}
```

## Notes

Tracking is currently disabled in the plugin--this is a very simple sender.  PRs for expanded functionality are welcome.

For convenience, a snapshot of [mailgun-php](https://github.com/mailgun/mailgun-php) is bundled.
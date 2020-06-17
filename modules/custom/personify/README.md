## Integration with Personify

### Requirements

#### SoapClient

1. Locate your php.ini file and find these two lines:
  extension=soap
  
2. Remove the “;” character at the beginning or add them to the file.

3. Restart your web server.

In case of error: 
```
PHP Warning:  PHP Startup: Unable to load dynamic library ...
```
Install missing extension. For example, for PHP 7.2:
```
sudo apt-get install php7.2-soap
```

### Credentials 

Add Personify credentials to your settings.php file.

```
# Personify SSO data.
$config['personify.settings']['prod_wsdl'] = ''
$config['personify.settings']['stage_wsdl'] = ''
$config['personify.settings']['vendor_id'] = ''
$config['personify.settings']['vendor_username'] = ''
$config['personify.settings']['vendor_password'] = ''
$config['personify.settings']['vendor_block'] = ''

# Personify Prod endpoint.
$config['personify.settings']['prod_endpoint'] = ''
$config['personify.settings']['prod_username'] = ''
$config['personify.settings']['prod_password'] = ''

# Personify Stage endpoint.
$config['personify.settings']['stage_endpoint'] = ''
$config['personify.settings']['stage_username'] = ''
$config['personify.settings']['stage_password'] = ''
```

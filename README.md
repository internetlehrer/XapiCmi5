ILIAS XapiCmi5 plugin
=============================

Copyright (c) 2018 internetlehrer GmbH

- Author:  Stefan Schneider <eqsoft4@gmail.com>, Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
- Forum: 
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "XapiCmi5 Plugin")


Requirements
------------

Ubuntu / Debian 

Dependant on php7.x Version:
package: php7.x-curl

Extra Server Configuration
--------------------------

- Basic Authentification headers MUST be transparent in php environment. Using apache with php-fpm behind reverse proxy (p.e. nginx) needs an explicit declaration like this:

```
<FilesMatch \.php$>
		SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
        
        .....
        
</FilesMatch>
```


Installation
------------

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *XapiCmi5*
(remove the branch suffix, e.g. -master).

1. Copy the XapiCmi5 directory to your ILIAS installation at the following path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/Repository/RepositoryObject
2. Go to Administration > Plugins
3. Choose action  "Update" for the XapiCmi5 plugin
4. Choose action  "Activate" for the XapiCmi5 plugin


If you use ILIAS 5.4 you should import the additional lang files:
.../XapiCmi5/Additional_for_ILIAS_5-4_de.lang for german
.../XapiCmi5/Additional_for_ILIAS_5-4_en.lang for english


Version History
===============

* All versions for ILIAS 5.3 and higher are maintained in GitHub


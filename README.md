# HEXAA authproc filter for SSP Attribute Authority and HEXAA backend

* Author: Gyula Szabó <gyufi@niif.hu>, NIIF Institute, Hungary

This module provides the HEXAA authprocfilter.

## Install module
You can install the module with composer:

    composer require niif/simplesamlphp-module-hexaa:1.*

### Authproc Filters
In the `config/config.php` you can define an array named "authproc.aa", just like authproc.sp or authproc.idp. The NameID of the request will be in the attribute as defined above. 

```
   authproc.aa = array(
       ...
       '60' => array(
            'class' => 'hexaa:Hexaa',
            'nameId_attribute_name' =>  'subject_nameid', // look at the aa authsource config
            'hexaa_api_url' =>          'https://www.hexaa.example.com/app.php/api',
            'hexaa_master_secret' =>    'you_can_get_it_from_the_hexaa_administrator'
       ),
```

### Attribute Authority
You shoud configure the Attribute Authority of the instance too, follow the instructions on the [AA documentation](https://github.com/NIIF/simplesamlphp-module-aa).

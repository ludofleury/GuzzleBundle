# Playbloom Guzzle Bundle

Provide a profiler and a symfony web profiler panel "swagger-like" for Guzzle

## Installation

Add the composer requirements (the bundle is WIP, so it's not added to packagist for the moment)
```javascript
{
    "require-dev": {
        "playbloom/guzzle-bundle": "dev-master"
    },

    "repositories": [
        {
            "type": "git",
            "url": "git://github.com/ludofleury/GuzzleBundle.git"
        }
    ]
}
```

Add the bundle to your Symfony app kernel
```php
<?php
    // in %your_project%/app/AppKernel.php

    if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        // ...
        $bundles[] = new Playbloom\Bundle\GuzzleBundle\PlaybloomGuzzleBundle();
    }

?>
```

Tag your guzzle client(s) service(s) with `playbloom_guzzle.client`.
For example:
```xml
<service id="acme.client"
    class="%acme.client.class%"
    factory-class="%acme.client.class%"
    factory-method="factory">
    <!-- your arguments -->
    <tag name="playbloom_guzzle.client" />
</service>
```

It will display a new section in the Symfony2 toolbar and a new panel in the Web profiler.

## Pre-requisites

* this profiler will only work with concrete guzzle client, see official [documentation]:(http://guzzlephp.org/tour/building_services.html#create-a-client)

* The bundle requires jquery to be defined at `%you_project%/web/js/lib/jquery/jquery.min.js`

## TODO

* Remove the strict jquery requirements
* Try to enabled the profiler for all guzzle client (configuration-based client)
* Add extra information about the client configuration itself (thanks to the guzzle service builder)
* Add clients|host|endpoint|time filters for http requests
